<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeCategories(int $count): array
{
    return collect(range(1, $count))
        ->map(fn (int $i) => Category::create([
            'name' => "Category {$i}",
            'icon' => "categories/icons/cat-{$i}.svg",
            'image' => "categories/cat-{$i}.png",
            'description' => "Category {$i} description",
            'is_active' => true,
        ])->id)
        ->all();
}

test('user can save up to 5 preferred categories', function () {
    $this->withoutMiddleware();

    $user = User::factory()->create();

    $this->actingAs($user, 'api')
        ->postJson('/api/user/onboarding/categories', [
            'category_ids' => makeCategories(5),
        ])
        ->assertSuccessful()
        ->assertJsonPath('message', 'Preferred categories saved.');

    expect($user->preferredCategories()->count())->toBe(5);
    expect($user->fresh()->onboarding_completed)->toBeTrue();
});

test('user cannot save more than 5 preferred categories', function () {
    $this->withoutMiddleware();

    $user = User::factory()->create();

    $this->actingAs($user, 'api')
        ->postJson('/api/user/onboarding/categories', [
            'category_ids' => makeCategories(6),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('category_ids');

    expect($user->preferredCategories()->count())->toBe(0);
});
