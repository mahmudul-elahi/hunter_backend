<?php

namespace App\Services;

use App\Models\AdminSetting;
use App\Models\Prediction;
use App\Models\PromoCode;
use App\Models\User;
use App\Notifications\AdminNewSubscriptionNotification;
use App\Notifications\AdminPaymentFailedNotification;
use App\Notifications\AdminPredictionResultNotification;
use App\Notifications\AdminPromoCodeUsedNotification;
use App\Notifications\PaymentFailedNotification;
use App\Notifications\PaymentSucceededNotification;
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

    public function sendAdminNewSubscription(User $subscriber): void
    {
        if (! $this->adminSettingEnabled('new_subscription')) {
            return;
        }

        $this->notifyAdmins(new AdminNewSubscriptionNotification($subscriber));
    }

    public function sendAdminPaymentFailed(User $subscriber): void
    {
        if (! $this->adminSettingEnabled('payment_failed')) {
            return;
        }

        $this->notifyAdmins(new AdminPaymentFailedNotification($subscriber));
    }

    public function sendAdminPromoCodeUsed(User $subscriber, PromoCode $promoCode): void
    {
        if (! $this->adminSettingEnabled('promo_code_used')) {
            return;
        }

        $this->notifyAdmins(new AdminPromoCodeUsedNotification($subscriber, $promoCode));
    }

    public function sendAdminPredictionResult(Prediction $prediction): void
    {
        if (! $this->adminSettingEnabled('prediction_result')) {
            return;
        }

        $this->notifyAdmins(new AdminPredictionResultNotification($prediction));
    }

    private function adminSettingEnabled(string $key): bool
    {
        $setting = AdminSetting::first();

        return $setting?->$key === true;
    }

    private function notifyAdmins(mixed $notification): void
    {
        User::role('admin')->each(fn (User $admin) => $admin->notify($notification));
    }
}
