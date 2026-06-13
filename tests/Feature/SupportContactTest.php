<?php

use App\Models\User;
use App\Notifications\SupportContactNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('user can send a support contact notification', function () {
    Notification::fake();

    config(['mail.from.address' => 'support@example.com']);

    $this->withoutMiddleware();

    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'first_name' => 'Jane',
        'last_name' => 'Doe',
    ]);

    $response = $this->actingAs($user, 'api')
        ->postJson('/api/support/contact', [
            'subject' => 'Billing question',
            'message' => 'Can you help with my invoice?',
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Support message sent successfully.');

    Notification::assertSentOnDemand(
        SupportContactNotification::class,
        function (SupportContactNotification $notification, array $channels, object $notifiable) use ($user): bool {
            $renderedMail = (string) $notification->toMail($notifiable)->render();

            return $notifiable->routes['mail'] === 'support@example.com'
                && $notification->user->is($user)
                && $notification->subject === 'Billing question'
                && $notification->supportMessage === 'Can you help with my invoice?'
                && str_contains($renderedMail, 'Can you help with my invoice?')
                && $channels === ['mail'];
        },
    );
});
