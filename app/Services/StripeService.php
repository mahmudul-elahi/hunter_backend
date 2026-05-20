<?php

namespace App\Services;

use App\Models\PromoCode;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Subscription;

class StripeService
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function startTrial(User $user, SubscriptionPlan $plan, string $paymentMethodId): Subscription
    {
        return DB::transaction(function () use ($user, $plan, $paymentMethodId): Subscription {
            $subscription = $user->newSubscription('default', $plan->stripe_price_id)
                ->trialDays(3)
                ->create($paymentMethodId);

            $user->update(['is_premium' => true]);

            $this->notificationService->sendTrialStarted($user);

            return $subscription;
        });
    }

    public function startTrialWithPromo(User $user, SubscriptionPlan $plan, string $paymentMethodId, PromoCode $promoCode): Subscription
    {
        return DB::transaction(function () use ($user, $plan, $paymentMethodId, $promoCode): Subscription {
            $subscription = $user->newSubscription('default', $plan->stripe_price_id)
                ->trialDays(3)
                ->withCoupon($promoCode->code)
                ->create($paymentMethodId);

            $user->update(['is_premium' => true, 'promo_code' => $promoCode->code]);

            $promoCode->increment('used_count');

            $this->notificationService->sendTrialStarted($user);
            $this->notificationService->sendAdminPromoCodeUsed($user, $promoCode);

            return $subscription;
        });
    }

    public function cancel(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->subscription('default')->cancel();
            // is_premium stays true until period ends — webhook handles the final flip
            $this->notificationService->sendSubscriptionCancelled($user);
        });
    }
}
