<?php

use App\Models\Category;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

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

test('an admin can list categories with active prediction counts', function () {
    actingAsAdmin();

    $category = makeCategory(['name' => 'Sports']);
    makePrediction(['category_id' => $category->id, 'status' => 'active']);
    makePrediction(['category_id' => $category->id, 'status' => 'win']);

    $this->getJson('/api/admin/categories')
        ->assertOk()
        ->assertJsonPath('message', 'Categories retrieved.')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.active_predictions_count', 1);
});

test('an admin can create a category with an svg icon', function () {
    Storage::fake('public');

    actingAsAdmin();

    $this->postJson('/api/admin/categories', [
        'name' => 'Esports',
        'icon' => UploadedFile::fake()->create('icon.svg', 10, 'image/svg+xml'),
        'image' => UploadedFile::fake()->image('category.png'),
        'description' => 'Esports predictions',
        'is_active' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('message', 'Category created.')
        ->assertJsonPath('data.name', 'Esports');

    $this->assertDatabaseHas('categories', ['name' => 'Esports']);
});

test('an admin can view a single category', function () {
    actingAsAdmin();
    $category = makeCategory(['name' => 'Tennis']);

    $this->getJson("/api/admin/categories/{$category->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Category retrieved.')
        ->assertJsonPath('data.id', $category->id)
        ->assertJsonPath('data.name', 'Tennis');
});

test('an admin can update a category', function () {
    actingAsAdmin();
    $category = makeCategory(['name' => 'Old name']);

    $this->postJson("/api/admin/categories/{$category->id}", [
        'name' => 'New name',
        'description' => 'Updated description',
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Category updated.')
        ->assertJsonPath('data.name', 'New name');

    expect($category->fresh()->name)->toBe('New name');
});

test('an admin can delete a category and its stored files', function () {
    Storage::fake('public');

    actingAsAdmin();

    $category = makeCategory();
    Storage::disk('public')->put($category->icon, 'icon');
    Storage::disk('public')->put($category->image, 'image');

    $this->deleteJson("/api/admin/categories/{$category->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Category deleted.');

    Storage::disk('public')->assertMissing($category->icon);
    Storage::disk('public')->assertMissing($category->image);
    $this->assertSoftDeleted('categories', ['id' => $category->id]);
});
