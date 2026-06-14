<?php

use App\Notifications\NewPredictionNotification;
use App\Notifications\PredictionResultNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('the prediction overview returns aggregate counts', function () {
    actingAsAdmin();

    $category = makeCategory();
    makePrediction(['category_id' => $category->id, 'status' => 'win']);
    makePrediction(['category_id' => $category->id, 'status' => 'loss']);
    makePrediction(['category_id' => $category->id, 'status' => 'active']);

    $this->getJson('/api/admin/predictions/overview')
        ->assertOk()
        ->assertJsonPath('data.total_records', 3)
        ->assertJsonPath('data.active_predictions', 1)
        ->assertJsonPath('data.total_win', 1)
        ->assertJsonPath('data.overall_win_rate', 50);
});

test('the prediction index lists predictions and filters by status', function () {
    actingAsAdmin();

    $category = makeCategory();
    makePrediction(['category_id' => $category->id, 'status' => 'active', 'title' => 'Active one']);
    makePrediction(['category_id' => $category->id, 'status' => 'win', 'title' => 'Won one']);

    $this->getJson('/api/admin/predictions?status=win')
        ->assertOk()
        ->assertJsonPath('message', 'Predictions retrieved.')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Won one');
});

test('the prediction index can search by title', function () {
    actingAsAdmin();

    $category = makeCategory();
    makePrediction(['category_id' => $category->id, 'title' => 'Lakers special']);
    makePrediction(['category_id' => $category->id, 'title' => 'Celtics special']);

    $this->getJson('/api/admin/predictions?search=Lakers')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Lakers special');
});

test('an admin can create a prediction and notify premium users', function () {
    Notification::fake();

    actingAsAdmin();
    $category = makeCategory();
    $premium = makeUserWithRole('user', ['is_premium' => true]);

    $this->postJson('/api/admin/predictions', [
        'category_id' => $category->id,
        'title' => 'New pick',
        'scheduled_at' => now()->addDay()->toDateString(),
        'confidence_level' => 90,
        'signal' => 'strong',
        'reason' => 'Solid matchup.',
    ])
        ->assertCreated()
        ->assertJsonPath('message', 'Prediction created.')
        ->assertJsonPath('data.title', 'New pick');

    $this->assertDatabaseHas('predictions', ['title' => 'New pick', 'created_by' => auth('api')->id()]);

    Notification::assertSentTo($premium, NewPredictionNotification::class);
});

test('creating a prediction validates required fields', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/predictions', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['category_id', 'title', 'scheduled_at', 'confidence_level', 'signal', 'reason']);
});

test('an admin can view a single prediction', function () {
    actingAsAdmin();
    $prediction = makePrediction(['title' => 'Detail pick']);

    $this->getJson("/api/admin/predictions/{$prediction->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $prediction->id)
        ->assertJsonPath('data.title', 'Detail pick');
});

test('an admin can update an active prediction', function () {
    actingAsAdmin();
    $category = makeCategory();
    $prediction = makePrediction(['category_id' => $category->id, 'status' => 'active']);

    $this->putJson("/api/admin/predictions/{$prediction->id}", [
        'category_id' => $category->id,
        'title' => 'Updated title',
        'scheduled_at' => now()->addDay()->toDateString(),
        'confidence_level' => 70,
        'signal' => 'medium',
        'reason' => 'Revised reasoning.',
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Prediction updated.')
        ->assertJsonPath('data.title', 'Updated title');
});

test('a resolved prediction cannot be updated', function () {
    actingAsAdmin();
    $category = makeCategory();
    $prediction = makePrediction(['category_id' => $category->id, 'status' => 'win']);

    $this->putJson("/api/admin/predictions/{$prediction->id}", [
        'category_id' => $category->id,
        'title' => 'Nope',
        'scheduled_at' => now()->addDay()->toDateString(),
        'confidence_level' => 70,
        'signal' => 'medium',
        'reason' => 'Revised reasoning.',
    ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Cannot edit a prediction that has already been resolved.');
});

test('an admin can delete a prediction', function () {
    actingAsAdmin();
    $prediction = makePrediction();

    $this->deleteJson("/api/admin/predictions/{$prediction->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Prediction deleted.');

    $this->assertSoftDeleted('predictions', ['id' => $prediction->id]);
});

test('resolving a prediction updates the status and recalculates the category win rate', function () {
    Notification::fake();

    actingAsAdmin();
    $category = makeCategory();
    $prediction = makePrediction(['category_id' => $category->id, 'status' => 'active']);
    $premium = makeUserWithRole('user', ['is_premium' => true]);

    $this->patchJson("/api/admin/predictions/{$prediction->id}/status", ['status' => 'win'])
        ->assertOk()
        ->assertJsonPath('message', 'Prediction status updated.')
        ->assertJsonPath('data.status', 'win');

    expect((float) $category->fresh()->win_rate)->toBe(100.0);

    Notification::assertSentTo($premium, PredictionResultNotification::class);
});

test('the status of a resolved prediction cannot be changed again', function () {
    actingAsAdmin();
    $prediction = makePrediction(['status' => 'win']);

    $this->patchJson("/api/admin/predictions/{$prediction->id}/status", ['status' => 'loss'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Status cannot be changed once a prediction is resolved.');
});

test('updating a prediction status validates the allowed values', function () {
    actingAsAdmin();
    $prediction = makePrediction(['status' => 'active']);

    $this->patchJson("/api/admin/predictions/{$prediction->id}/status", ['status' => 'invalid'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');
});
