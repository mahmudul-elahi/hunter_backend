<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminPaymentFailedNotification extends Notification implements ShouldQueue
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
            ->subject('Payment Failed — User Subscription')
            ->view('emails.admin-payment-failed', ['user' => $this->subscriber]);
    }
}
