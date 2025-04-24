<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('chat.{userId}.{friendId}', function ($authUser, $userId, $friendId) {
    return (int)$authUser->id === (int)$userId || (int)$authUser->id === (int)$friendId;
});



