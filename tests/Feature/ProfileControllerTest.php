<?php

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('the me endpoint returns the authenticated user profile', function () {
    $user = actingAsUser(['first_name' => 'Jane', 'email' => 'jane@example.com']);

    $this->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('message', 'Profile retrieved.')
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.first_name', 'Jane')
        ->assertJsonPath('data.email', 'jane@example.com');
});

test('the user profile endpoint returns the authenticated user', function () {
    $user = actingAsUser();

    $this->getJson('/api/user/profile')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

test('the profile reports active premium for a subscription that has not expired', function () {
    $user = actingAsUser(['is_premium' => true]);
    $user->subscriptions()->create([
        'status' => 'active',
        'expires_at' => now()->addMonth(),
    ]);

    $this->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('data.is_premium', true)
        ->assertJsonPath('data.subscription.type', 'active');
});

test('the profile revokes premium and marks the subscription expired once the period ends', function () {
    $user = actingAsUser(['is_premium' => true]);
    $user->subscriptions()->create([
        'status' => 'active',
        'expires_at' => now()->subMinute(),
    ]);

    $this->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('data.is_premium', false)
        ->assertJsonPath('data.subscription.type', 'expired');
});

test('a user can update simple profile fields', function () {
    actingAsUser(['first_name' => 'Old']);

    $this->postJson('/api/user/profile', [
        'first_name' => 'New',
        'gender' => 'female',
        'location' => 'Dhaka',
    ])
        ->assertOk()
        ->assertJsonPath('data.first_name', 'New')
        ->assertJsonPath('data.gender', 'female')
        ->assertJsonPath('data.location', 'Dhaka');
});

test('profile update rejects an invalid gender', function () {
    actingAsUser();

    $this->postJson('/api/user/profile', ['gender' => 'unknown'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('gender');
});

test('a user can change their password with the correct current password', function () {
    Notification::fake();

    actingAsUser();

    $this->putJson('/api/user/change-password', [
        'current_password' => 'password',
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Password changed successfully.');
});

test('change password fails when the current password is wrong', function () {
    actingAsUser();

    $this->putJson('/api/user/change-password', [
        'current_password' => 'wrong-password',
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Current password is incorrect.');
});

test('a user can delete their avatar', function () {
    Storage::fake('public');

    $user = actingAsUser(['avatar' => 'avatars/current.jpg']);
    Storage::disk('public')->put($user->avatar, 'avatar contents');

    $this->deleteJson('/api/user/profile/avatar')
        ->assertOk()
        ->assertJsonPath('message', 'Avatar deleted.');

    Storage::disk('public')->assertMissing('avatars/current.jpg');
    expect($user->fresh()->avatar)->toBeNull();
});

test('deleting an avatar succeeds when the user has none', function () {
    actingAsUser(['avatar' => null]);

    $this->deleteJson('/api/user/profile/avatar')
        ->assertOk()
        ->assertJsonPath('message', 'Avatar deleted.');
});
