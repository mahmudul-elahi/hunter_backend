<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('a verified active user can log in and receive a token', function () {
    $user = User::factory()->create(['email' => 'jane@example.com']);
    $user->assignRole('user');

    $this->postJson('/api/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Login successful.')
        ->assertJsonPath('data.token_type', 'bearer')
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonStructure(['data' => ['access_token', 'token_type', 'expires_in', 'user' => ['id', 'email']]]);
});

test('login fails with invalid credentials', function () {
    $user = User::factory()->create(['email' => 'jane@example.com']);
    $user->assignRole('user');

    $this->postJson('/api/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'wrong-password',
    ])
        ->assertStatus(401)
        ->assertJsonPath('status', false)
        ->assertJsonPath('message', 'Invalid credentials.');
});

test('a deactivated user cannot log in', function () {
    $user = User::factory()->create(['email' => 'jane@example.com', 'is_active' => false]);
    $user->assignRole('user');

    $this->postJson('/api/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'password',
    ])
        ->assertStatus(403)
        ->assertJsonPath('message', 'Account is deactivated.');
});

test('a non-user account cannot log in through the user endpoint', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $admin->assignRole('admin');

    $this->postJson('/api/auth/login', [
        'email' => 'admin@example.com',
        'password' => 'password',
    ])
        ->assertStatus(403)
        ->assertJsonPath('message', 'Unauthorized access.');
});

test('an unverified user is asked to verify and is issued a fresh otp', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create(['email' => 'jane@example.com']);
    $user->assignRole('user');

    $this->postJson('/api/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('data.email_verified', false);

    $this->assertDatabaseHas('otp_codes', [
        'email' => 'jane@example.com',
        'type' => 'email_verification',
    ]);
});

test('login validation requires an email and password', function () {
    $this->postJson('/api/auth/login', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

test('login stores the device token when an fcm token is supplied', function () {
    $user = User::factory()->create(['email' => 'jane@example.com']);
    $user->assignRole('user');

    $this->postJson('/api/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'password',
        'fcm_token' => 'device-token-123',
    ])->assertOk();

    $this->assertDatabaseHas('user_device_tokens', [
        'user_id' => $user->id,
        'token' => 'device-token-123',
    ]);
});

test('an authenticated user can log out with a valid token', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $token = auth('api')->login($user);

    $this->withToken($token)
        ->postJson('/api/auth/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Logged out successfully.');
});

test('an authenticated user can refresh their token', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $token = auth('api')->login($user);

    $this->withToken($token)
        ->postJson('/api/auth/refresh')
        ->assertOk()
        ->assertJsonPath('data.token_type', 'bearer')
        ->assertJsonStructure(['data' => ['access_token', 'expires_in']]);
});
