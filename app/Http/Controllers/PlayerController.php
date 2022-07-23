<?php

namespace App\Http\Controllers;

use App\Events\MessageEvent;
use App\Jobs\Counter;
use App\Jobs\DisconnectPlayer;
use App\Models\Joueur;
use App\Models\WebSocketMessage;
use App\Providers\ChannelManagerCustom;
use http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlayerController extends Controller
{

    public function getPlayers(): JsonResponse
    {
        return response()->json(Joueur::all(), 200);
    }

    public function getPlayerById($id): JsonResponse
    {
        $player = Joueur::find($id);
        if (is_null($player)) {
            return response()->json(['message' => 'player not found'], 404);
        }
        return response()->json($player, 200);
    }

    public function getPlayerByName($name): JsonResponse
    {
        $player = Joueur::whereRaw('LOWER(`nom`) LIKE ? ',strtolower($name))->get();
        if (is_null($player)) {
            return response()->json(['message' => 'player not found'], 404);
        }
        return response()->json($player, 200);
    }


    public function addplayer(Request $request): JsonResponse
    {
        $player =Joueur::create($request->all());
        return response()->json($player, 201);
    }

    public function updateplayer(Request $request, $id): JsonResponse
    {
        $player = Joueur::find($id);
        if (is_null($player)) {
            return response()->json(['message' => 'player not found'], 404);
        }
        $player["chevalet"] = $request["chevalet"];
        $player->update();
        return response()->json($player, 200);
    }


    public function deleteplayer($id): JsonResponse
    {
        $player = Joueur::find($id);
        if (is_null($player)) {
            return response()->json(['message' => 'player not found'], 404);
        }
        $player->delete();
        return response()->json(['message' => 'player deleted successfully'], 200);
    }

    public function getImage($fileName): JsonResponse
    {

        $path = public_path().'/public/image/'.$fileName;
        $infoPath = pathinfo($path);
        $extension = $infoPath['extension'];
        $image = file_get_contents($path);
        return response()->json('data:image/' . $extension . ';base64,' . base64_encode($image ), 200);
    }

    public function uploadImage($playerId, Request $request): JsonResponse
    {
        $player = Joueur::find($playerId);

        if($request->file('image')){
            $file= $request->file('image');
            $filename= date('YmdHi').$file->getClientOriginalName();
            $file-> move(public_path('public/Image'), $filename);
            $player['image']= $filename;
            $player->save();
            return response()->json(['message' => 'image saved'], 200);
        }
        return response()->json(['message' => 'error'], 500);
    }



    public function updateStatus($playerId): JsonResponse
    {
        $player = Joueur::find($playerId);

        $player["pingedAt"] = date("Y-m-d H:i:s",strtotime('+1 hour'));
        $player->update();
        if($player["disconnected"]) {
            $player["disconnected"] = false;
            $player->update();
            event(new MessageEvent(new WebSocketMessage("playerJoined",$player["partieId"],$playerId, null, null, null)));
        }
        return response()->json(["message" => "done"], 200);
    }

    public function disconnectUser($playerId): JsonResponse
    {
        $player = Joueur::find($playerId);

        $player["disconnected"] = true;
        $player->update();
        event(new MessageEvent(new WebSocketMessage("playerLeft",$player["partieId"],$playerId, null, null, null)));
        DisconnectPlayer::dispatch($player["id"], $player["pingedAt"])
            ->delay(now()->addMinute())
            ->onConnection('database');
        if($player["statusJoueur"]) {
            sleep(5);
            $player = Joueur::find($this->id);
            if($player["disconnected"]) {
                (new Counter($player["partieId"], true, $player["id"]))->updateTimer();
            }
        }
        return response()->json(["message" => "done"], 200);
    }


}
