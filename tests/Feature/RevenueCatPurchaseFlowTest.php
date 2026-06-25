<?php

use App\Models\User;
use App\Notifications\PaymentSucceededNotification;
use App\Notifications\SubscriptionCancelledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'revenuecat.webhook_authorization' => 'secret-token',
        'revenuecat.secret_api_key' => 'sk_test_key',
        'revenuecat.project_id' => 'proj_real',
        'revenuecat.premium_entitlement_id' => 'premium',
    ]);
});

test('an initial purchase webhook syncs the subscription and grants premium', function () {
    Notification::fake();

    $user = User::factory()->create(['is_premium' => false]);

    $startsAtMs = now()->timestamp * 1000;
    $endsAtMs = now()->addMonth()->timestamp * 1000;

    Http::fake([
        "*/customers/{$user->id}/subscriptions" => Http::response(['items' => [[
            'product_id' => 'premium_monthly',
            'store' => 'APP_STORE',
            'environment' => 'PRODUCTION',
            'gives_access' => true,
            'status' => 'active',
            'starts_at' => $startsAtMs,
            'current_period_ends_at' => $endsAtMs,
        ]]]),
        "*/customers/{$user->id}/active-entitlements" => Http::response(['items' => [['entitlement_id' => 'premium']]]),
        "*/customers/{$user->id}" => Http::response(['id' => 'rc_cust_123']),
    ]);

    $payload = ['event' => [
        'id' => 'evt_purchase_1',
        'type' => 'INITIAL_PURCHASE',
        'app_user_id' => (string) $user->id,
        'product_id' => 'premium_monthly',
        'price' => 9.99,
        'currency' => 'USD',
        'store' => 'APP_STORE',
        'environment' => 'PRODUCTION',
        'purchased_at_ms' => $startsAtMs,
    ]];

    $this->postJson('/api/webhook/revenuecat', $payload, ['Authorization' => 'secret-token'])
        ->assertOk();

    expect($user->fresh()->is_premium)->toBeTrue();

    $this->assertDatabaseHas('subscriptions', [
        'user_id' => $user->id,
        'status' => 'active',
        'revenuecat_product_id' => 'premium_monthly',
        'store' => 'APP_STORE',
        'currency' => 'USD',
    ]);

    expect((float) DB::table('subscriptions')->where('user_id', $user->id)->value('price'))->toBe(9.99);
    expect(DB::table('revenuecat_webhook_events')->where('event_id', 'evt_purchase_1')->value('processed_at'))->not->toBeNull();

    Notification::assertSentTo($user, PaymentSucceededNotification::class);
});

test('an expiration webhook revokes premium and marks the subscription expired', function () {
    Notification::fake();

    $user = User::factory()->create(['is_premium' => true]);

    $startsAtMs = now()->subMonth()->timestamp * 1000;
    $endedAtMs = now()->subMinute()->timestamp * 1000;

    // After expiry the V2 API reports the subscription as expired with no access
    // and no active entitlements.
    Http::fake([
        "*/customers/{$user->id}/subscriptions" => Http::response(['items' => [[
            'product_id' => 'premium_monthly',
            'store' => 'APP_STORE',
            'environment' => 'PRODUCTION',
            'gives_access' => false,
            'status' => 'expired',
            'starts_at' => $startsAtMs,
            'current_period_ends_at' => $endedAtMs,
        ]]]),
        "*/customers/{$user->id}/active-entitlements" => Http::response(['items' => []]),
        "*/customers/{$user->id}" => Http::response(['id' => 'rc_cust_123']),
    ]);

    $this->postJson('/api/webhook/revenuecat', ['event' => [
        'id' => 'evt_expire_1',
        'type' => 'EXPIRATION',
        'app_user_id' => (string) $user->id,
        'product_id' => 'premium_monthly',
        'environment' => 'PRODUCTION',
    ]], ['Authorization' => 'secret-token'])
        ->assertOk();

    expect($user->fresh()->is_premium)->toBeFalse();

    $this->assertDatabaseHas('subscriptions', [
        'user_id' => $user->id,
        'status' => 'expired',
        'revenuecat_product_id' => 'premium_monthly',
    ]);

    expect(DB::table('revenuecat_webhook_events')->where('event_id', 'evt_expire_1')->value('processed_at'))->not->toBeNull();

    Notification::assertSentTo($user, SubscriptionCancelledNotification::class);
});

test('a misconfigured project id makes the customer lookup fail and the event is left unprocessed', function () {
    $user = User::factory()->create(['is_premium' => false]);

    // Simulate RevenueCat rejecting the request (e.g. placeholder project id / wrong key).
    Http::fake(['*' => Http::response(['error' => 'not found'], 404)]);

    $this->postJson('/api/webhook/revenuecat', ['event' => [
        'id' => 'evt_purchase_2',
        'type' => 'INITIAL_PURCHASE',
        'app_user_id' => (string) $user->id,
    ]], ['Authorization' => 'secret-token'])
        ->assertStatus(500);

    expect($user->fresh()->is_premium)->toBeFalse();
    $this->assertDatabaseCount('subscriptions', 0);
    expect(DB::table('revenuecat_webhook_events')->where('event_id', 'evt_purchase_2')->value('processed_at'))->toBeNull();
});
