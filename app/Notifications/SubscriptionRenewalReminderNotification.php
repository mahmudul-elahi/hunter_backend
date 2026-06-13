<?php

namespace App\Notifications;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionRenewalReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Carbon $renewalDate) {}

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail', 'broadcast', 'database'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Subscription Renews in 3 Days')
            ->view('emails.subscription-renewal-reminder', [
                'user' => $notifiable,
                'renewalDate' => $this->renewalDate,
            ]);
    }

    public function toBroadcast(User $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'title' => 'Subscription Renewal Reminder',
            'message' => 'Your subscription will renew on ' . $this->renewalDate->format('M j, Y') . '. Make sure your payment method is up to date.',
        ];
    }
}
