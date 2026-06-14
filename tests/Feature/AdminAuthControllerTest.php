<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('an admin can log in and receive a token', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $admin->assignRole('admin');

    $this->postJson('/api/admin/login', [
        'email' => 'admin@example.com',
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Login successful.')
        ->assertJsonPath('data.user.id', $admin->id)
        ->assertJsonStructure(['data' => ['access_token', 'token_type', 'expires_in', 'user' => ['id', 'email']]]);
});

test('admin login fails with invalid credentials', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $admin->assignRole('admin');

    $this->postJson('/api/admin/login', [
        'email' => 'admin@example.com',
        'password' => 'nope',
    ])
        ->assertStatus(401)
        ->assertJsonPath('message', 'Invalid credentials.');
});

test('a non-admin cannot log in through the admin endpoint', function () {
    $user = User::factory()->create(['email' => 'jane@example.com']);
    $user->assignRole('user');

    $this->postJson('/api/admin/login', [
        'email' => 'jane@example.com',
        'password' => 'password',
    ])
        ->assertStatus(403)
        ->assertJsonPath('message', 'Unauthorized access.');
});

test('a deactivated admin cannot log in', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com', 'is_active' => false]);
    $admin->assignRole('admin');

    $this->postJson('/api/admin/login', [
        'email' => 'admin@example.com',
        'password' => 'password',
    ])
        ->assertStatus(403)
        ->assertJsonPath('message', 'Account is deactivated.');
});

test('an authenticated admin can log out', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $token = auth('api')->login($admin);

    $this->withToken($token)
        ->postJson('/api/admin/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Logged out successfully.');
});

test('an authenticated admin can refresh their token', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $token = auth('api')->login($admin);

    $this->withToken($token)
        ->postJson('/api/admin/refresh')
        ->assertOk()
        ->assertJsonStructure(['data' => ['access_token', 'token_type', 'expires_in']]);
});
