<?php

namespace App\Services;

use App\Models\AdminSetting;
use App\Models\Prediction;
use App\Models\User;
use App\Notifications\AdminNewSubscriptionNotification;
use App\Notifications\AdminPaymentFailedNotification;
use App\Notifications\AdminPredictionResultNotification;
use App\Notifications\NewPredictionNotification;
use App\Notifications\PasswordChangedNotification;
use App\Notifications\PaymentFailedNotification;
use App\Notifications\PaymentSucceededNotification;
use App\Notifications\SubscriptionCancelledNotification;
use App\Notifications\SubscriptionRenewalReminderNotification;
use App\Notifications\WelcomeNotification;
use Carbon\Carbon;

class NotificationService
{
    public function sendWelcome(User $user): void
    {
        $user->notify(new WelcomeNotification);
    }

    public function sendPasswordChanged(User $user): void
    {
        $user->notify(new PasswordChangedNotification);
    }

    public function sendNewPrediction(Prediction $prediction): void
    {
        User::where('is_premium', true)->lazy()->each(
            fn(User $user) => $user->notify(new NewPredictionNotification($prediction))
        );
    }

    public function sendSubscriptionRenewalReminder(User $user, Carbon $renewalDate): void
    {
        $user->notify(new SubscriptionRenewalReminderNotification($renewalDate));
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
        User::role('admin')->each(fn(User $admin) => $admin->notify($notification));
    }
}
