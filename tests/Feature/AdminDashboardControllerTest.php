<?php

use App\Models\Subscription;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('the dashboard overview aggregates win rate, subscribers and revenue', function () {
    actingAsAdmin();

    $category = makeCategory();
    makePrediction(['category_id' => $category->id, 'status' => 'win']);
    makePrediction(['category_id' => $category->id, 'status' => 'win']);
    makePrediction(['category_id' => $category->id, 'status' => 'loss']);
    makePrediction(['category_id' => $category->id, 'status' => 'active']);

    $premium = makeUserWithRole('user', ['is_premium' => true]);

    Subscription::create([
        'user_id' => $premium->id,
        'status' => 'active',
        'price' => 9.99,
        'currency' => 'USD',
        'purchased_at' => now(),
    ]);

    $this->getJson('/api/admin/dashboard/overview')
        ->assertOk()
        ->assertJsonPath('message', 'Dashboard overview retrieved.')
        ->assertJsonPath('data.overall_win_rate', 66.67)
        ->assertJsonPath('data.active_predictions', 1)
        ->assertJsonPath('data.total_subscribers', 1)
        ->assertJsonPath('data.monthly_revenue', 9.99);
});

test('the overview reports a zero win rate when nothing is resolved', function () {
    actingAsAdmin();

    $this->getJson('/api/admin/dashboard/overview')
        ->assertOk()
        ->assertJsonPath('data.overall_win_rate', 0)
        ->assertJsonPath('data.total_subscribers', 0);
});

test('the win rate chart returns categories that have a win rate', function () {
    actingAsAdmin();

    makeCategory(['name' => 'With rate', 'win_rate' => 75.00]);
    makeCategory(['name' => 'No rate', 'win_rate' => null]);

    $this->getJson('/api/admin/dashboard/win-rate-chart')
        ->assertOk()
        ->assertJsonPath('message', 'Win rate chart data retrieved.')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'With rate');
});

test('recent predictions returns the latest predictions with their category', function () {
    actingAsAdmin();

    $category = makeCategory();
    makePrediction(['category_id' => $category->id, 'title' => 'Recent pick']);

    $this->getJson('/api/admin/dashboard/recent-predictions')
        ->assertOk()
        ->assertJsonPath('message', 'Recent predictions retrieved.')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Recent pick');
});
