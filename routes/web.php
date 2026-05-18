<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

if (! app()->isProduction()) {
    Route::get('/stripe-test', fn () => view('stripe-test'));
}
