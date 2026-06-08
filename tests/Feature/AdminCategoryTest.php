<?php

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('admin category icon must be an svg file', function () {
    Storage::fake('public');

    $this->withoutMiddleware();

    $response = $this->postJson('/api/admin/categories', [
        'name' => 'Esports',
        'icon' => UploadedFile::fake()->image('icon.png'),
        'image' => UploadedFile::fake()->image('category.png'),
        'description' => 'Esports predictions',
        'is_active' => true,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['icon']);
});

test('admin can toggle a category status', function () {
    $this->withoutMiddleware();

    $category = Category::create([
        'name' => 'Sports',
        'icon' => 'categories/icons/sports.svg',
        'image' => 'categories/sports.png',
        'description' => 'Sports predictions',
        'is_active' => true,
    ]);

    $this->patchJson("/api/admin/categories/{$category->id}/status")
        ->assertSuccessful()
        ->assertJsonPath('message', 'Category deactivated.')
        ->assertJsonPath('data.is_active', false);

    expect($category->fresh()->is_active)->toBeFalse();
});
