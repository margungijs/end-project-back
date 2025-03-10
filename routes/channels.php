<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.1.2', function ($user) {
    return true;
});
