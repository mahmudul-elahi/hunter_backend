<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use App\Services\RevenueCatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function __construct(private readonly RevenueCatService $revenueCatService) {}

    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::where('is_active', true)->get();

        return $this->successResponse('Plans retrieved.', [
            'app_user_id' => Auth::user()->revenueCatAppUserId(),
            'premium_entitlement_id' => config('revenuecat.premium_entitlement_id'),
            'plans' => SubscriptionPlanResource::collection($plans),
        ]);
    }

    public function mySubscription(): JsonResponse
    {
        $user = Auth::user()->load(['subscriptions.plan']);
        $subscription = $user->subscriptions->first();

        return $this->successResponse('Subscription retrieved.', [
            'app_user_id' => $user->revenueCatAppUserId(),
            'premium_entitlement_id' => config('revenuecat.premium_entitlement_id'),
            'is_premium' => $user->is_premium,
            'subscription_type' => $subscription?->status ?? 'none',
            'subscription' => $subscription ? [
                'status' => $subscription->status,
                'plan' => $subscription->plan?->name,
                'product_id' => $subscription->revenuecat_product_id,
                'store' => $subscription->store,
                'environment' => $subscription->environment,
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                'expires_at' => $subscription->expires_at?->toIso8601String(),
                'cancelled_at' => $subscription->cancelled_at?->toIso8601String(),
                'billing_issue_at' => $subscription->billing_issue_at?->toIso8601String(),
            ] : null,
        ]);
    }

    public function sync(): JsonResponse
    {
        $user = Auth::user();

        try {
            $customerInfo = $this->revenueCatService->getSubscriber($user->revenueCatAppUserId());
            $subscription = $this->revenueCatService->syncUser($user, $customerInfo);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to sync subscription: '.$e->getMessage(), 422);
        }

        return $this->successResponse('Subscription synced.', [
            'is_premium' => $user->fresh()->is_premium,
            'subscription_status' => $subscription->status,
        ]);
    }

    public function cancel(): JsonResponse
    {
        return $this->successResponse('Subscription cancellation is managed from the App Store or Google Play subscription settings.');
    }
}
