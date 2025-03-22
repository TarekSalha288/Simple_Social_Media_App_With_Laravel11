<?php

use App\Models\Post;
use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

Broadcast::channel('room.{roomId}', function () {
    return true;
});
Broadcast::channel('user.{id}', function (User $user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('notification.{userId}',function(){
return true;
});