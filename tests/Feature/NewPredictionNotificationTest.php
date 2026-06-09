<?php

use App\Models\Category;
use App\Models\Prediction;
use App\Models\User;
use App\Models\UserDeviceToken;
use App\Notifications\NewPredictionNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\Fcm\FcmChannel;

uses(RefreshDatabase::class);

test('new prediction notification includes firebase channel and payload', function () {
    $prediction = makePredictionForNotificationTest();
    $notifiable = User::factory()->create();

    UserDeviceToken::create([
        'user_id' => $notifiable->id,
        'token' => 'fcm-token',
    ]);

    $notification = new NewPredictionNotification($prediction);
    $fcmMessage = $notification->toFcm($notifiable)->toArray();

    expect($notification->via($notifiable))
        ->toContain(FcmChannel::class)
        ->and($fcmMessage['notification'])
        ->toBe([
            'title' => 'New Prediction Available',
            'body' => $prediction->title,
        ])
        ->and($fcmMessage['data'])
        ->toBe([
            'type' => 'new_prediction',
            'prediction_id' => (string) $prediction->id,
        ]);
});

test('new prediction notifications are sent only to premium users', function () {
    Notification::fake();

    $premiumUser = User::factory()->create(['is_premium' => true]);
    $freeUser = User::factory()->create(['is_premium' => false]);
    $prediction = makePredictionForNotificationTest();

    UserDeviceToken::create([
        'user_id' => $premiumUser->id,
        'token' => 'premium-fcm-token',
    ]);

    app(NotificationService::class)->sendNewPrediction($prediction);

    Notification::assertSentTo(
        $premiumUser,
        function (NewPredictionNotification $notification, array $channels) use ($prediction): bool {
            return $notification->prediction->is($prediction)
                && in_array(FcmChannel::class, $channels, true);
        },
    );

    Notification::assertNotSentTo($freeUser, NewPredictionNotification::class);
});

test('user routes firebase notifications to registered device tokens', function () {
    $user = User::factory()->create();

    UserDeviceToken::create([
        'user_id' => $user->id,
        'token' => 'first-fcm-token',
    ]);

    UserDeviceToken::create([
        'user_id' => $user->id,
        'token' => 'second-fcm-token',
    ]);

    expect($user->routeNotificationForFcm())->toBe([
        'first-fcm-token',
        'second-fcm-token',
    ]);
});

function makePredictionForNotificationTest(): Prediction
{
    $admin = User::factory()->create();

    $category = Category::create([
        'name' => 'Sports',
        'icon' => 'categories/icons/sports.svg',
        'image' => 'categories/sports.png',
        'description' => 'Sports predictions',
        'is_active' => true,
    ]);

    return Prediction::create([
        'category_id' => $category->id,
        'title' => 'Lakers moneyline',
        'scheduled_at' => now()->addDay(),
        'confidence_level' => 85,
        'signal' => 'strong',
        'reason' => 'Momentum and matchup advantage.',
        'detailed_summary' => 'Premium prediction summary.',
        'created_by' => $admin->id,
    ]);
}
