<?php

namespace App\Notifications;

use App\Models\Prediction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewPredictionNotification extends Notification implements ShouldQueue
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
            ->subject('New Prediction Available: '.$this->prediction->title)
            ->view('emails.new-prediction', [
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
            'message' => 'New prediction available: '.$this->prediction->title,
            'prediction_id' => $this->prediction->id,
        ];
    }
}
