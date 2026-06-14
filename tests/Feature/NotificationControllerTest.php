<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function createNotificationFor(User $user, array $attributes = []): string
{
    $id = (string) Str::uuid();

    $user->notifications()->create(array_merge([
        'id' => $id,
        'type' => 'App\\Notifications\\WelcomeNotification',
        'data' => ['message' => 'Welcome aboard.'],
    ], $attributes));

    return $id;
}

test('a user can list their notifications with pagination meta', function () {
    $user = actingAsUser();

    createNotificationFor($user);
    createNotificationFor($user);

    $this->getJson('/api/notifications')
        ->assertOk()
        ->assertJsonPath('message', 'Notifications retrieved.')
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.pagination.total', 2)
        ->assertJsonStructure(['data' => [['id', 'type', 'data', 'read_at', 'created_at']], 'meta' => ['pagination']]);
});

test('a user only sees their own notifications', function () {
    $user = actingAsUser();
    $other = User::factory()->create();

    createNotificationFor($user);
    createNotificationFor($other);

    $this->getJson('/api/notifications')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('a user can mark a single notification as read', function () {
    $user = actingAsUser();
    $id = createNotificationFor($user);

    $this->putJson("/api/notifications/{$id}/read")
        ->assertOk()
        ->assertJsonPath('message', 'Notification marked as read.');

    expect($user->notifications()->find($id)->read_at)->not->toBeNull();
});

test('marking another users notification returns a not found', function () {
    actingAsUser();
    $other = User::factory()->create();
    $id = createNotificationFor($other);

    $this->putJson("/api/notifications/{$id}/read")
        ->assertNotFound();
});

test('a user can mark all notifications as read', function () {
    $user = actingAsUser();
    createNotificationFor($user);
    createNotificationFor($user);

    $this->putJson('/api/notifications/read-all')
        ->assertOk()
        ->assertJsonPath('message', 'All notifications marked as read.');

    expect($user->notifications()->whereNull('read_at')->count())->toBe(0);
});
