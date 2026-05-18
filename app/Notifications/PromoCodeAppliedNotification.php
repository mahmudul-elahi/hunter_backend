<?php

namespace App\Notifications;

use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PromoCodeAppliedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly PromoCode $promoCode) {}

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Promo Code Applied Successfully')
            ->view('emails.promo-code-applied', [
                'user' => $notifiable,
                'promoCode' => $this->promoCode,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'message' => 'Promo code '.$this->promoCode->code.' applied successfully.',
            'code' => $this->promoCode->code,
        ];
    }
}
