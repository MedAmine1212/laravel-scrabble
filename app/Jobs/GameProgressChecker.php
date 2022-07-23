<?php

namespace App\Jobs;

use App\Events\MessageEvent;
use App\Models\Joueur;
use App\Models\Partie;
use App\Models\WebSocketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GameProgressChecker implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $gameId;
    protected $game;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($gameId)
    {
        $this->gameId = $gameId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {


        $this->game = Partie::find($this->gameId);
        if($this->game["statusPartie"] == "enCours") {

        $joueurs= Joueur::where('partieId', $this->gameId)->get();
        if(sizeof($joueurs) <= 1) {
            foreach ($joueurs as $j) {
                    $j["voted"] = false;
                    $j->update();
            }
            $this->game["statusPartie"] ="Finie";
            $this->game->update();
            $message = new WebSocketMessage("gameEnded", $this->gameId,null,null, null, null);
            event(new MessageEvent($message));
        } else {
        foreach ($joueurs as $joueur){
            if(!$joueur["disconnected"]) {
            CheckUserStatus::dispatch($joueur["id"])
                ->onConnection('database');;
            }
        }
        }
            sleep(6);
            GameProgressChecker::dispatch($this->gameId)
                ->onConnection('database');
        }
    }
}
