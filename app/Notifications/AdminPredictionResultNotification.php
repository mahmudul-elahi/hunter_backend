<?php

namespace App\Notifications;

use App\Models\Prediction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminPredictionResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Prediction $prediction) {}

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
            ->subject('Prediction Result Published: '.$this->prediction->title)
            ->view('emails.admin-prediction-result', ['prediction' => $this->prediction]);
    }
}
