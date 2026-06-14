<?php

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('the user overview counts only accounts with the user role', function () {
    actingAsAdmin();

    makeUserWithRole('user', ['is_active' => true]);
    makeUserWithRole('user', ['is_active' => false]);

    $this->getJson('/api/admin/users/overview')
        ->assertOk()
        ->assertJsonPath('message', 'User overview retrieved.')
        ->assertJsonPath('data.total_users', 2)
        ->assertJsonPath('data.active_users', 1)
        ->assertJsonPath('data.new_today', 2);
});

test('the user index lists users and excludes admins', function () {
    actingAsAdmin();

    makeUserWithRole('user', ['first_name' => 'Regular']);

    $this->getJson('/api/admin/users')
        ->assertOk()
        ->assertJsonPath('message', 'Users retrieved.')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.first_name', 'Regular');
});

test('the user index can search by name or email', function () {
    actingAsAdmin();

    makeUserWithRole('user', ['first_name' => 'Findme', 'email' => 'findme@example.com']);
    makeUserWithRole('user', ['first_name' => 'Hidden', 'email' => 'hidden@example.com']);

    $this->getJson('/api/admin/users?search=findme')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.email', 'findme@example.com');
});

test('the user index can filter by premium status', function () {
    actingAsAdmin();

    makeUserWithRole('user', ['is_premium' => true, 'email' => 'premium@example.com']);
    makeUserWithRole('user', ['is_premium' => false, 'email' => 'free@example.com']);

    $this->getJson('/api/admin/users?is_premium=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.email', 'premium@example.com');
});

test('an admin can view a single user', function () {
    actingAsAdmin();
    $user = makeUserWithRole('user', ['first_name' => 'Target']);

    $this->getJson("/api/admin/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('message', 'User retrieved.')
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.first_name', 'Target');
});

test('an admin can toggle a user status', function () {
    actingAsAdmin();
    $user = makeUserWithRole('user', ['is_active' => true]);

    $this->patchJson("/api/admin/users/{$user->id}/status")
        ->assertOk()
        ->assertJsonPath('message', 'User deactivated.');

    expect($user->fresh()->is_active)->toBeFalse();

    $this->patchJson("/api/admin/users/{$user->id}/status")
        ->assertOk()
        ->assertJsonPath('message', 'User activated.');
});

test('an admin status cannot be toggled', function () {
    actingAsAdmin();
    $otherAdmin = makeUserWithRole('admin');

    $this->patchJson("/api/admin/users/{$otherAdmin->id}/status")
        ->assertStatus(403)
        ->assertJsonPath('message', 'Cannot change status of admin users.');
});
