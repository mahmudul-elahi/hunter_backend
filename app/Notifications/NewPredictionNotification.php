<?php

namespace App\Notifications;

use App\Models\Prediction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class NewPredictionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Prediction $prediction) {}

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        $channels = ['mail', 'broadcast', 'database'];

        if ($notifiable->routeNotificationForFcm($this) !== []) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Prediction Available: ' . $this->prediction->title)
            ->view('emails.new-prediction', [
                'user' => $notifiable,
                'prediction' => $this->prediction,
            ]);
    }

    public function toBroadcast(User $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toFcm(User $notifiable): FcmMessage
    {
        return (new FcmMessage(
            notification: new FcmNotification(
                title: 'New Prediction Available',
                body: $this->prediction->title,
            ),
        ))
            ->data([
                'type' => 'new_prediction',
                'prediction_id' => (string) $this->prediction->id,
            ])
            ->custom([
                'android' => [
                    'notification' => [
                        'sound' => 'default',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ],
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'title' => 'New Prediction Available',
            'message' => 'New prediction available: ' . $this->prediction->title,
            'prediction_id' => $this->prediction->id,
        ];
    }
}
