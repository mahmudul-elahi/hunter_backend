<?php

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('the categories endpoint returns only active categories with active prediction counts', function () {
    actingAsUser();

    $active = makeCategory(['name' => 'Active']);
    makeCategory(['name' => 'Inactive', 'is_active' => false]);

    makePrediction(['category_id' => $active->id, 'status' => 'active']);
    makePrediction(['category_id' => $active->id, 'status' => 'win']);

    $this->getJson('/api/categories')
        ->assertOk()
        ->assertJsonPath('message', 'Categories retrieved.')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Active')
        ->assertJsonPath('data.0.active_predictions_count', 1);
});

test('a free user cannot list category predictions', function () {
    actingAsUser(['is_premium' => false]);
    $category = makeCategory();

    $this->getJson("/api/predictions/category/{$category->id}")
        ->assertStatus(403)
        ->assertJsonPath('message', 'This feature is available for premium subscribers only.')
        ->assertJsonPath('errors.premium_required', true);
});

test('a premium user lists only active predictions for a category', function () {
    actingAsUser(['is_premium' => true]);
    $category = makeCategory();

    makePrediction(['category_id' => $category->id, 'status' => 'active', 'title' => 'Active pick']);
    makePrediction(['category_id' => $category->id, 'status' => 'win', 'title' => 'Resolved pick']);

    $this->getJson("/api/predictions/category/{$category->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Predictions retrieved.')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Active pick');
});

test('a premium user whose subscription has expired is denied without waiting for the webhook', function () {
    $user = actingAsUser(['is_premium' => true]);
    $user->subscriptions()->create([
        'status' => 'active',
        'expires_at' => now()->subMinute(),
    ]);
    $category = makeCategory();

    $this->getJson("/api/predictions/category/{$category->id}")
        ->assertStatus(403)
        ->assertJsonPath('errors.premium_required', true);
});

test('a premium user with a subscription expiring in the future keeps access', function () {
    $user = actingAsUser(['is_premium' => true]);
    $user->subscriptions()->create([
        'status' => 'active',
        'expires_at' => now()->addMonth(),
    ]);
    $category = makeCategory();

    makePrediction(['category_id' => $category->id, 'status' => 'active', 'title' => 'Active pick']);

    $this->getJson("/api/predictions/category/{$category->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('premium category predictions can be filtered by title', function () {
    actingAsUser(['is_premium' => true]);
    $category = makeCategory();

    makePrediction(['category_id' => $category->id, 'status' => 'active', 'title' => 'Lakers win']);
    makePrediction(['category_id' => $category->id, 'status' => 'active', 'title' => 'Celtics win']);

    $this->getJson("/api/predictions/category/{$category->id}?title=Lakers")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Lakers win');
});

test('a free user cannot view a single prediction', function () {
    actingAsUser(['is_premium' => false]);
    $prediction = makePrediction();

    $this->getJson("/api/predictions/{$prediction->id}")
        ->assertStatus(403)
        ->assertJsonPath('errors.premium_required', true);
});

test('a premium user can view a single prediction', function () {
    actingAsUser(['is_premium' => true]);
    $prediction = makePrediction(['title' => 'Single pick']);

    $this->getJson("/api/predictions/{$prediction->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Prediction retrieved.')
        ->assertJsonPath('data.id', $prediction->id)
        ->assertJsonPath('data.title', 'Single pick');
});

test('viewing a missing prediction returns not found for a premium user', function () {
    actingAsUser(['is_premium' => true]);

    $this->getJson('/api/predictions/99999')
        ->assertNotFound();
});
