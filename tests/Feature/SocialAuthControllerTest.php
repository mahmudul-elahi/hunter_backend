<?php

use App\Exceptions\SocialAuthException;
use App\Models\User;
use App\Services\SocialAuthService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('google login returns a token for a valid social token', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->mock(SocialAuthService::class)
        ->shouldReceive('authenticate')
        ->once()
        ->with('google', 'valid-token')
        ->andReturn($user);

    $this->postJson('/api/auth/google', ['token' => 'valid-token'])
        ->assertOk()
        ->assertJsonPath('message', 'Login successful.')
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonStructure(['data' => ['access_token', 'token_type', 'expires_in', 'user']]);
});

test('apple login returns a token for a valid social token', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->mock(SocialAuthService::class)
        ->shouldReceive('authenticate')
        ->once()
        ->with('apple', 'valid-token')
        ->andReturn($user);

    $this->postJson('/api/auth/apple', ['token' => 'valid-token'])
        ->assertOk()
        ->assertJsonPath('data.user.id', $user->id);
});

test('social login surfaces the exception status and message', function () {
    $this->mock(SocialAuthService::class)
        ->shouldReceive('authenticate')
        ->once()
        ->andThrow(SocialAuthException::invalidToken());

    $this->postJson('/api/auth/google', ['token' => 'bad-token'])
        ->assertStatus(401)
        ->assertJsonPath('status', false)
        ->assertJsonPath('message', 'Invalid or expired token.');
});

test('social login requires a token', function () {
    $this->postJson('/api/auth/google', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('token');
});

test('social login stores the device token when an fcm token is supplied', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->mock(SocialAuthService::class)
        ->shouldReceive('authenticate')
        ->once()
        ->andReturn($user);

    $this->postJson('/api/auth/google', [
        'token' => 'valid-token',
        'fcm_token' => 'social-device-token',
    ])->assertOk();

    $this->assertDatabaseHas('user_device_tokens', [
        'user_id' => $user->id,
        'token' => 'social-device-token',
    ]);
});
