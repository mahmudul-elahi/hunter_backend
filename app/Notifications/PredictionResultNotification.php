<?php

namespace App\Notifications;

use App\Models\Prediction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PredictionResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Prediction $prediction) {}

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
            ->subject('Prediction Result: '.$this->prediction->title)
            ->view('emails.prediction-result', [
                'user' => $notifiable,
                'prediction' => $this->prediction,
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
            'message' => 'Prediction result for '.$this->prediction->title.': '.strtoupper($this->prediction->status),
            'prediction_id' => $this->prediction->id,
            'status' => $this->prediction->status,
        ];
    }
}
