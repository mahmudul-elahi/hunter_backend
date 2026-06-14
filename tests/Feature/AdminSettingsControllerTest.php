<?php

use App\Models\AdminSetting;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('the admin profile is returned from both me and settings endpoints', function () {
    $admin = actingAsAdmin(['first_name' => 'Super', 'email' => 'admin@example.com']);

    $this->getJson('/api/admin/me')
        ->assertOk()
        ->assertJsonPath('data.id', $admin->id)
        ->assertJsonPath('data.email', 'admin@example.com');

    $this->getJson('/api/admin/settings/profile')
        ->assertOk()
        ->assertJsonPath('data.first_name', 'Super');
});

test('an admin can update their profile and avatar', function () {
    Storage::fake('public');

    actingAsAdmin(['first_name' => 'Old']);

    $this->postJson('/api/admin/settings/profile', [
        'first_name' => 'Updated',
        'avatar' => UploadedFile::fake()->image('admin.jpg'),
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Profile updated.')
        ->assertJsonPath('data.first_name', 'Updated');
});

test('an admin can change their password', function () {
    actingAsAdmin();

    $this->putJson('/api/admin/settings/password', [
        'current_password' => 'password',
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Password changed successfully.');
});

test('admin password change fails with a wrong current password', function () {
    actingAsAdmin();

    $this->putJson('/api/admin/settings/password', [
        'current_password' => 'wrong',
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Current password is incorrect.');
});

test('notification settings are created with defaults on first read', function () {
    actingAsAdmin();

    $this->getJson('/api/admin/settings/notifications')
        ->assertOk()
        ->assertJsonPath('message', 'Notification settings retrieved.')
        ->assertJsonPath('data.new_subscription', true)
        ->assertJsonPath('data.payment_failed', true)
        ->assertJsonPath('data.prediction_result', true);

    expect(AdminSetting::count())->toBe(1);
});

test('an admin can update notification settings', function () {
    actingAsAdmin();

    $this->putJson('/api/admin/settings/notifications', [
        'new_subscription' => false,
        'payment_failed' => false,
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Notification settings updated.')
        ->assertJsonPath('data.new_subscription', false)
        ->assertJsonPath('data.payment_failed', false)
        ->assertJsonPath('data.prediction_result', true);
});
