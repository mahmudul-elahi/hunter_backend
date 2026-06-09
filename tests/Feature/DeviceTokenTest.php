<?php

use App\Models\User;
use App\Models\UserDeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setActiveDeviceToken replaces all existing tokens with the new one', function () {
    $user = User::factory()->create();

    UserDeviceToken::create(['user_id' => $user->id, 'token' => 'old-token-1']);
    UserDeviceToken::create(['user_id' => $user->id, 'token' => 'old-token-2']);

    $user->setActiveDeviceToken('new-token');

    expect($user->deviceTokens()->count())->toBe(1);

    $this->assertDatabaseHas('user_device_tokens', [
        'user_id' => $user->id,
        'token' => 'new-token',
    ]);

    $this->assertDatabaseMissing('user_device_tokens', ['token' => 'old-token-1']);
    $this->assertDatabaseMissing('user_device_tokens', ['token' => 'old-token-2']);
});

test('setActiveDeviceToken takes a token away from another user', function () {
    $previousUser = User::factory()->create();
    $currentUser = User::factory()->create();

    UserDeviceToken::create(['user_id' => $previousUser->id, 'token' => 'shared-token']);

    $currentUser->setActiveDeviceToken('shared-token');

    $this->assertDatabaseHas('user_device_tokens', [
        'user_id' => $currentUser->id,
        'token' => 'shared-token',
    ]);

    expect(UserDeviceToken::where('token', 'shared-token')->count())->toBe(1);
});
