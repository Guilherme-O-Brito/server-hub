<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('execution-slots', function (User $user) {
    return true;
});

Broadcast::channel('users.{userId}.servers', function (User $user, int $userId) {
    return $user->id === $userId;
});