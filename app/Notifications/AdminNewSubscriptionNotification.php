<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminNewSubscriptionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly User $subscriber) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Subscription Purchase')
            ->view('emails.admin-new-subscription', ['user' => $this->subscriber]);
    }
}
