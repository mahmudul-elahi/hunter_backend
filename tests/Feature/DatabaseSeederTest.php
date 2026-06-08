<?php

use App\Models\AdminSetting;
use App\Models\Category;
use App\Models\Prediction;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('database seeder creates model-aligned demo data', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::where('email', 'admin@picksempire.com')->first())->not->toBeNull()
        ->and(User::where('email', 'john@example.com')->first())->not->toBeNull()
        ->and(AdminSetting::query()->count())->toBe(1)
        ->and(Category::query()->count())->toBe(4)
        ->and(Prediction::query()->count())->toBe(24)
        ->and(Subscription::query()->count())->toBe(1);

    Category::query()
        ->pluck('icon')
        ->each(fn (string $icon) => expect($icon)->toEndWith('.svg'));
});
