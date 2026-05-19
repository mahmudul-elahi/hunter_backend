<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\ApplyPromoRequest;
use App\Http\Requests\Subscription\StartTrialRequest;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\PromoCode;
use App\Models\SubscriptionPlan;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function __construct(private readonly StripeService $stripeService) {}

    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::where('is_active', true)->get();

        return $this->successResponse('Plans retrieved.', SubscriptionPlanResource::collection($plans));
    }

    public function mySubscription(): JsonResponse
    {
        $user = Auth::user();
        $subscription = $user->subscription('default');

        $type = match (true) {
            $subscription?->onTrial() => 'trial',
            $subscription?->active() && ! $subscription->onTrial() => 'paid',
            $subscription?->canceled() => 'cancelled',
            $subscription?->ended() => 'ended',
            default => 'none',
        };

        return $this->successResponse('Subscription retrieved.', [
            'is_premium' => $user->is_premium,
            'subscription_type' => $type,
            'subscription' => $subscription ? [
                'status' => $subscription->stripe_status,
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                'ends_at' => $subscription->ends_at?->toIso8601String(),
            ] : null,
        ]);
    }

    public function startTrial(StartTrialRequest $request): JsonResponse
    {
        $user = Auth::user();

        if ($user->subscribed('default')) {
            return $this->errorResponse('You already have an active subscription.', 422);
        }

        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        try {
            $this->stripeService->startTrial($user, $plan, $request->payment_method_id);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to start trial: '.$e->getMessage(), 422);
        }

        return $this->successResponse('Trial started successfully.', ['is_premium' => true]);
    }

    public function validatePromo(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $promoCode = PromoCode::where('code', $request->code)->first();

        if (! $promoCode || ! $promoCode->isValid()) {
            return $this->errorResponse('This promo code is invalid or has expired.', 422);
        }

        return $this->successResponse('Promo code is valid.', [
            'code' => $promoCode->code,
            'discount' => $promoCode->discount,
            'type' => $promoCode->type,
        ]);
    }

    public function applyPromo(ApplyPromoRequest $request): JsonResponse
    {
        $user = Auth::user();

        if ($user->subscribed('default')) {
            return $this->errorResponse('You already have an active subscription.', 422);
        }

        $promoCode = PromoCode::where('code', $request->code)->first();

        if (! $promoCode || ! $promoCode->isValid()) {
            return $this->errorResponse('This promo code is invalid or has expired.', 422);
        }

        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        try {
            $this->stripeService->startTrialWithPromo($user, $plan, $request->payment_method_id, $promoCode);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to apply promo code: '.$e->getMessage(), 422);
        }

        return $this->successResponse('Subscription started with promo code.', ['is_premium' => true]);
    }

    public function cancel(): JsonResponse
    {
        $user = Auth::user();

        if (! $user->subscribed('default')) {
            return $this->errorResponse('You do not have an active subscription.', 422);
        }

        try {
            $this->stripeService->cancel($user);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to cancel subscription: '.$e->getMessage(), 422);
        }

        return $this->successResponse('Subscription cancelled successfully.');
    }
}
