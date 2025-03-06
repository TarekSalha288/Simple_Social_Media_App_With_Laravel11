<?php
use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

Broadcast::channel('room.{roomId}', function (User $user,$roomId) {
    return $user;
});