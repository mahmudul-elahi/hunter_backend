<?php

namespace App\Services;

use App\Models\PromoCode;
use App\Models\User;
use App\Notifications\PaymentFailedNotification;
use App\Notifications\PaymentSucceededNotification;
use App\Notifications\PromoCodeAppliedNotification;
use App\Notifications\SubscriptionCancelledNotification;
use App\Notifications\TrialStartedNotification;

class NotificationService
{
    public function sendTrialStarted(User $user): void
    {
        $user->notify(new TrialStartedNotification);
    }

    public function sendPaymentSucceeded(User $user): void
    {
        $user->notify(new PaymentSucceededNotification);
    }

    public function sendPaymentFailed(User $user): void
    {
        $user->notify(new PaymentFailedNotification);
    }

    public function sendSubscriptionCancelled(User $user): void
    {
        $user->notify(new SubscriptionCancelledNotification);
    }

    public function sendPromoCodeApplied(User $user, PromoCode $promoCode): void
    {
        $user->notify(new PromoCodeAppliedNotification($promoCode));
    }
}
