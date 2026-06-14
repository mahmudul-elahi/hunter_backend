<?php

use App\Models\User;
use App\Services\RevenueCatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function webhookPayload(string $eventId, string $appUserId, string $type = 'INITIAL_PURCHASE'): array
{
    return [
        'event' => [
            'id' => $eventId,
            'app_user_id' => $appUserId,
            'type' => $type,
        ],
    ];
}

test('the webhook rejects a request with the wrong authorization header', function () {
    config(['revenuecat.webhook_authorization' => 'secret-token']);

    $this->postJson('/api/webhook/revenuecat', webhookPayload('evt_1', '1'), [
        'Authorization' => 'wrong-token',
    ])->assertStatus(401);

    $this->assertDatabaseCount('revenuecat_webhook_events', 0);
});

test('the webhook returns 422 when event id or app user id is missing', function () {
    config(['revenuecat.webhook_authorization' => null]);

    $this->postJson('/api/webhook/revenuecat', ['event' => ['type' => 'INITIAL_PURCHASE']])
        ->assertStatus(422);
});

test('the webhook skips processing when the user is unknown', function () {
    config(['revenuecat.webhook_authorization' => null]);

    $this->postJson('/api/webhook/revenuecat', webhookPayload('evt_unknown', '99999'))
        ->assertOk();

    $this->assertDatabaseHas('revenuecat_webhook_events', [
        'event_id' => 'evt_unknown',
        'processed_at' => null,
    ]);
});

test('the webhook processes a known user and marks the event processed', function () {
    config(['revenuecat.webhook_authorization' => 'secret-token']);

    $user = User::factory()->create();

    $mock = $this->mock(RevenueCatService::class);
    $mock->shouldReceive('getSubscriber')->once()->with((string) $user->id)->andReturn(['customer' => 'info']);
    $mock->shouldReceive('syncUser')->once();

    $this->postJson('/api/webhook/revenuecat', webhookPayload('evt_success', (string) $user->id), [
        'Authorization' => 'secret-token',
    ])->assertOk();

    expect(DB::table('revenuecat_webhook_events')->where('event_id', 'evt_success')->value('processed_at'))->not->toBeNull();
});

test('an already processed event is not processed again', function () {
    config(['revenuecat.webhook_authorization' => null]);

    $user = User::factory()->create();

    DB::table('revenuecat_webhook_events')->insert([
        'event_id' => 'evt_done',
        'event_type' => 'INITIAL_PURCHASE',
        'app_user_id' => (string) $user->id,
        'payload' => json_encode([]),
        'processed_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $mock = $this->mock(RevenueCatService::class);
    $mock->shouldReceive('getSubscriber')->never();
    $mock->shouldReceive('syncUser')->never();

    $this->postJson('/api/webhook/revenuecat', webhookPayload('evt_done', (string) $user->id))
        ->assertOk();
});

test('the webhook returns 500 when processing fails so revenuecat retries', function () {
    config(['revenuecat.webhook_authorization' => null]);

    $user = User::factory()->create();

    $mock = $this->mock(RevenueCatService::class);
    $mock->shouldReceive('getSubscriber')->once()->andThrow(new RuntimeException('boom'));

    $this->postJson('/api/webhook/revenuecat', webhookPayload('evt_fail', (string) $user->id))
        ->assertStatus(500);

    $this->assertDatabaseHas('revenuecat_webhook_events', [
        'event_id' => 'evt_fail',
        'processed_at' => null,
    ]);
});
