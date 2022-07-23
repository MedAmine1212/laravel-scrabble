<?php

namespace App\Http\Controllers;

use App\Events\MessageEvent;
use App\Jobs\CalculateScore;
use App\Jobs\GameProgressChecker;
use App\Models\Joueur;
use App\Models\Partie;
use App\Models\WebSocketMessage;
use Illuminate\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Jobs\Counter;
use Illuminate\Support\Facades\Session;

class GameController extends Controller
{
    public $message;

    /**
     * Récupérer Partie avec un type précis
     * @OA\Get (
     *     path="/api/getAvailableGame/{type}",
     *     tags={"Gestions parties"},
     *     @OA\Parameter(
     *         in="path",
     *         name="type",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="success"
     * )
     * )
     */
    public function getAvailableGame($type, $playerId): JsonResponse
    {

        // check if there's a game
        $game = Partie::whereRaw("statusPartie = 'enAttente' AND typePartie LIKE ? ",$type)->get();

        if (!$game->isEmpty())
        {
            foreach ($game as $g){
                $game = $g;
                break;
            }
        }else {
            // else create a new game
            $game =new Partie();
            $game["typePartie"] = $type;
            $date = date("Y-m-d H:i:s",strtotime('+1 hour'));
            $game["dateCreation"] = $date;
            $game["statusPartie"] = "enAttente";
            $game["currentJobId"] = "empty";
            $game["grille"] ="";
            for ($i = 0; $i <= 224; $i++) {
                $game["grille"] = $game["grille"]." ";
            }
            $game->save();
        }
        // join the game and update
        $player = Joueur::find($playerId);
        $player["partieId"] = $game["id"];
        $player->update();
        $joueurs= Joueur::where('partieId', $game["id"])->get();
        if($joueurs->count()< $game["typePartie"]) {
            // if !reached number of players required
            $this->message = new WebSocketMessage("join",$game["id"],$joueurs->count(), null,null, null);
        }else {
             // if reached
            $reserve = $game["reserve"];
            $order = [];
            for ($i = 0; $i < $game["typePartie"]; $i++) {
                $order[$i] = $i+1;
            }
            shuffle($order);
            $k = 0;
            foreach ($joueurs as $j){
                error_log("'".$j["chevalet"]."'");
                $j["chevalet"] = "";
                for ($x = 0; $x <= 6; $x++) {
                    $pos = rand(0, strlen($reserve) - 1);
                    $j['chevalet'] = $j['chevalet'].$reserve[$pos];
                    $reserve = substr_replace($reserve, '', $pos, 1);
                }
                $j["ordre"] = $order[$k++];
                if($j["ordre"] == 1) {
                    $j["statusJoueur"] = true;
                } else {

                    $j["statusJoueur"] = false;
                }
                $j->update();
            }
            $game["statusPartie"] = "enCours";
            $game["reserve"] = $reserve;
            $date = date("Y-m-d H:i:s",strtotime('+1 hour'));
            $game["dateDebutPartie"] = $date;
            $game["tempsJoueur"] = 300;
            $date = date("Y-m-d H:i:s",strtotime('+1 hour'));
            $game["counterLastUpdated"] = $date;
            $game->update();
            $this->message = new WebSocketMessage("startGame",$game["id"],$joueurs->count(), null, null, null);
            $counter  =  (new Counter($game["id"], false, null))
                ->delay(now()->addMinutes(5))
                ->onConnection('database');
            $id = app(Dispatcher::class)->dispatch($counter);
            $game["currentJobId"] = $id;
            $game->update();

            GameProgressChecker::dispatch($game["id"])
                ->onConnection('database');

        }
        event(new MessageEvent($this->message));
        $game["joueurs"] = Joueur::where('partieId', $game["id"])->get();
        return response()->json($game, 201);
    }
    public function leaveGame($playerId, $gameId):JsonResponse
    {
        $player = Joueur::find($playerId);
        $player["partieId"] = null;
        $player["disconnected"] = false;
        $player["score"] = 0;
        $player["voted"] = false;
        $player["chevalet"] = "";
        $player->update();
        $game = Partie::find($gameId);
        if($game["statusPartie"] == "enAttente") {
            $joueurs= Joueur::where('partieId', $gameId)->get();
            $this->message = new WebSocketMessage("leave",$gameId,$joueurs->count(), null,null, null);
            event(new MessageEvent($this->message));
        } else if($game["statusPartie"] == "enCours") {

            $this->message = new WebSocketMessage("playerDisconnected", $gameId, $playerId,null, null, null);
            event(new MessageEvent($this->message));
            if($player["statusJoueur"]) {
                (new Counter($gameId, false, null))->updateTimer();
            }
        }
        return response()->json(['Messae' => 'success'], 200);
    }

    /**
     * Récupérer Partie avec ID
     * @OA\Get (
     *     path="/api/getGameById/{gameId}",
     *     tags={"Gestions parties"},
     *     @OA\Parameter(
     *         in="path",
     *         name="gameId",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="success"
     * )
     * )
     */
    public function getGameById($gameId):JsonResponse
    {
        $game = Partie::find($gameId);
        $joueurs= Joueur::where('partieId', $gameId)->get();
        $game["joueurs"] = $joueurs;
        if (is_null($game)) {
            return response()->json(['message' => 'game not found'], 404);
        }
        return response()->json($game, 200);
    }


    public function voteEndGame($gameId, $playerId):JsonResponse
    {
        $game = Partie::find($gameId);
        if (is_null($game)) {
            return response()->json(['message' => 'game not found'], 404);
        }
        $player = Joueur::find($playerId);
        $player["voted"] = true;
        $game["endVotes"] = $game["endVotes"]+1;
        $game->update();
        $player->update();
        $this->message = new WebSocketMessage("voteEnd", $gameId, $player["id"], $player["nom"], null, null);
        event(new MessageEvent($this->message));
        if($game["endVotes"] == $game["typePartie"]) {

            sleep(2);
            $joueurs= Joueur::where('partieId', $gameId)->get();

            foreach ($joueurs as $j){
                $j["voted"] = false;
                $j->update();
            }
            $game["statusPartie"] ="Finie";
            $game->update();
            $message = new WebSocketMessage("gameEnded", $game["id"],null,null, null, null);
            event(new MessageEvent($message));
        }
        return response()->json(["message" => "success"], 200);
    }
}
