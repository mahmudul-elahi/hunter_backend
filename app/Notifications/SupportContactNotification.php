<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportContactNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly User $user,
        public readonly string $subject,
        public readonly string $supportMessage,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Support: '.$this->subject)
            ->replyTo($this->user->email, $this->user->full_name)
            ->view('emails.support-contact', [
                'user' => $this->user,
                'subject' => $this->subject,
                'supportMessage' => $this->supportMessage,
            ]);
    }
}
