<?php

namespace App\Notifications;

use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminPromoCodeUsedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly User $subscriber,
        public readonly PromoCode $promoCode,
    ) {}

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
            ->subject('Promo Code Used')
            ->view('emails.admin-promo-code-used', [
                'user' => $this->subscriber,
                'promoCode' => $this->promoCode,
            ]);
    }
}
