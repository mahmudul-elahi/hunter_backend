<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
 * Private channel for user notifications.
 * Laravel broadcasts notifications on: App.Models.User.{id}
 * Frontend subscribes to: private-App.Models.User.{id}
 */
Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return $user->id === $id;
});
