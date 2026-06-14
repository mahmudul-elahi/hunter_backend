<?php

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

dataset('protected user routes', [
    'me' => ['get', '/api/me'],
    'user profile' => ['get', '/api/user/profile'],
    'notifications' => ['get', '/api/notifications'],
    'categories' => ['get', '/api/categories'],
    'support contact' => ['post', '/api/support/contact'],
]);

dataset('protected admin routes', [
    'dashboard overview' => ['get', '/api/admin/dashboard/overview'],
    'users index' => ['get', '/api/admin/users'],
    'predictions index' => ['get', '/api/admin/predictions'],
    'categories index' => ['get', '/api/admin/categories'],
    'admin profile' => ['get', '/api/admin/me'],
]);

test('protected user routes reject guests', function (string $method, string $uri) {
    $this->json($method, $uri)->assertUnauthorized();
})->with('protected user routes');

test('protected admin routes reject guests', function (string $method, string $uri) {
    $this->json($method, $uri)->assertUnauthorized();
})->with('protected admin routes');

test('user routes reject an admin without the user role', function (string $method, string $uri) {
    actingAsAdmin();

    $this->json($method, $uri)->assertForbidden();
})->with('protected user routes');

test('admin routes reject a user without the admin role', function (string $method, string $uri) {
    actingAsUser();

    $this->json($method, $uri)->assertForbidden();
})->with('protected admin routes');
