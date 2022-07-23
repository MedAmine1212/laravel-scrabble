<?php

use App\Models\Joueur;
use App\Models\Partie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/


Broadcast::channel('player.{id}', function ($player, $id) {
    Auth::check();
    return ["id"=> $player->id];
});


