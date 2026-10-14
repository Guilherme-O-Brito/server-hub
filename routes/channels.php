<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('execution-slots', function (User $user) {
    return true;
});
