<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


//get all players
Route::get('/players', 'App\Http\Controllers\PlayerController@getPlayers');

//get a specific player
Route::get('/player/{id}', 'App\Http\Controllers\PlayerController@getPlayerById');

//get a specific player with name
Route::get('/playerByName/{name}', 'App\Http\Controllers\PlayerController@getPlayerByname');


//Add player
Route::post('/addPlayer', 'App\Http\Controllers\PlayerController@addPlayer');

//Update player
Route::put('/updatePlayer/{id}', 'App\Http\Controllers\PlayerController@updatePlayer');


//Delete player
Route::delete('/deletePlayer/{id}', 'App\Http\Controllers\PlayerController@deletePlayer');


//update status
Route::get('/updateStatus/{playerId}','App\Http\Controllers\PlayerController@updateStatus');


//disconnect
Route::get('/disconnected/{playerId}','App\Http\Controllers\PlayerController@disconnectUser');



//get image
Route::get('/getImage/{fileName}','App\Http\Controllers\PlayerController@getImage');


//Upload Image
Route::post('/uploadImage/{playerId}','App\Http\Controllers\PlayerController@uploadImage');


//Get available game
Route::get('/getAvailableGame/{type}/{playerId}','App\Http\Controllers\GameController@getAvailableGame');

//get game by id
Route::get('/getGameById/{gameId}','App\Http\Controllers\GameController@getGameById');

//leave game
Route::get('/leaveGame/{playerId}/{gameId}','App\Http\Controllers\GameController@leaveGame');

//vote end game
Route::get('/voteEndGame/{gameId}/{playerId}','App\Http\Controllers\GameController@voteEndGame');



//send message
Route::post('/sendMessage/{gameId}','App\Http\Controllers\MessageController@sendMessage');

//execute command
Route::post('/sendCommand/{gameId}','App\Http\Controllers\MessageController@sendCommand');


//get all by game
Route::get('/getAllByGame/{gameId}','App\Http\Controllers\MessageController@getAllByGame');



//give token

Route::group(['middleware' => 'cors'], function($router){
    Passport::routes();
});

//revoke token

Route::middleware('auth:api')->get('/token/revoke', function (Request $request) {
    DB::table('oauth_access_tokens')
        ->where('id', $request->user()->id)
        ->update([
            'revoked' => true
        ]);
    return response()->json('DONE');
});
