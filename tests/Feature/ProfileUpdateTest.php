<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('user can update profile fields and avatar in the same request', function () {
    Storage::fake('public');

    $this->withoutMiddleware();

    $user = User::factory()->create([
        'avatar' => 'avatars/old-avatar.jpg',
        'first_name' => 'Old',
        'last_name' => 'Name',
    ]);

    Storage::disk('public')->put($user->avatar, 'old avatar');

    $response = $this->actingAs($user, 'api')
        ->postJson('/api/user/profile', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'location' => 'Dhaka',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Profile updated.')
        ->assertJsonPath('data.first_name', 'Jane')
        ->assertJsonPath('data.last_name', 'Doe')
        ->assertJsonPath('data.location', 'Dhaka');

    $user->refresh();

    expect($user->avatar)->toStartWith('avatars/')
        ->and($user->avatar)->not->toBe('avatars/old-avatar.jpg');

    Storage::disk('public')->assertMissing('avatars/old-avatar.jpg');
    Storage::disk('public')->assertExists($user->avatar);
});
