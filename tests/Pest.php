<?php

use App\Models\Category;
use App\Models\Prediction;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Ensure the api-guarded roles exist before assigning them in tests.
 */
function seedRoles(): void
{
    test()->seed(RoleSeeder::class);
}

/**
 * Create a user with the given role without authenticating as them.
 *
 * @param  array<string, mixed>  $attributes
 */
function makeUserWithRole(string $role = 'user', array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole($role);

    return $user;
}

/**
 * Create a user with the "user" role and authenticate as them on the api guard.
 *
 * @param  array<string, mixed>  $attributes
 */
function actingAsUser(array $attributes = []): User
{
    $user = makeUserWithRole('user', $attributes);

    test()->actingAs($user, 'api');

    return $user;
}

/**
 * Create a user with the "admin" role and authenticate as them on the api guard.
 *
 * @param  array<string, mixed>  $attributes
 */
function actingAsAdmin(array $attributes = []): User
{
    $admin = makeUserWithRole('admin', $attributes);

    test()->actingAs($admin, 'api');

    return $admin;
}

/**
 * Create a category with sensible defaults.
 *
 * @param  array<string, mixed>  $attributes
 */
function makeCategory(array $attributes = []): Category
{
    return Category::create(array_merge([
        'name' => 'Sports',
        'icon' => 'categories/icons/sports.svg',
        'image' => 'categories/sports.png',
        'description' => 'Sports predictions',
        'is_active' => true,
    ], $attributes));
}

/**
 * Create a prediction with sensible defaults, creating a category when none is given.
 *
 * @param  array<string, mixed>  $attributes
 */
function makePrediction(array $attributes = []): Prediction
{
    $categoryId = $attributes['category_id'] ?? makeCategory()->id;
    $createdBy = $attributes['created_by'] ?? User::factory()->create()->id;

    return Prediction::create(array_merge([
        'title' => 'Lakers moneyline',
        'scheduled_at' => now()->addDay(),
        'confidence_level' => 80,
        'signal' => 'strong',
        'reason' => 'Momentum and matchup advantage.',
        'detailed_summary' => 'Premium prediction summary.',
        'status' => 'active',
    ], $attributes, [
        'category_id' => $categoryId,
        'created_by' => $createdBy,
    ]));
}
