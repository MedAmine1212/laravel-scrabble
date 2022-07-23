<?php

namespace App\Jobs;

use App\Events\MessageEvent;
use App\Models\Joueur;
use App\Models\Partie;
use App\Models\WebSocketMessage;
use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class Counter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $gameId;
    protected $message;
    protected $check;
    protected $playerId;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($gameId, $check, $playerId)
    {
        $this->gameId = $gameId;
        $this->check = $check;
        $this->playerId = $playerId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $game = Partie::find($this->gameId);
        if(isset($game["id"])) {

            $joueurs= Joueur::where('partieId', $this->gameId)->get();
            if($game["statusPartie"] == "enCours" && $joueurs->count() > 1) {
                if($this->job->getJobId() == $game["currentJobId"]) {
                    $this->updateTimer();
            } else {
                    $this->delete();
                    error_log("job dropped");
                }
            }
        }
    }

    public function updateTimer() {
        if($this->check) {
            $player = Joueur::find($this->playerId);
            if(!$player["disconnected"]) {
                return;
            }
        }
        $game = Partie::find($this->gameId);
        error_log("new Counter set");

        $players = Joueur::whereRaw("statusJoueur = true AND partieId = ? ", $this->gameId)->get();
        $order = 0;
        foreach ($players as $player) {
            $player["statusJoueur"] = false;
            $player->update();
            $order = $player["ordre"] + 1;
        }
        if ($order > $game["typePartie"]) {
            $order = 1;
        }
        $players1 = Joueur::whereRaw("ordre =? AND partieId =?", [$order, $this->gameId])->get();
        while($players1->isEmpty()){
            $order++;
            if ($order > $game["typePartie"]) {
                $order = 1;
            }
            $players1 = Joueur::whereRaw("ordre =? AND partieId =?", [$order, $this->gameId])->get();
        }
        foreach ($players1 as $player1) {
            $players1 = $player1;
        }
        $players1["statusJoueur"] = true;
        $players1->update();
        $counter  =  (new Counter($this->gameId, false, null))
            ->delay(now()->addMinutes(5))
            ->onConnection('database');
        $id = app(Dispatcher::class)->dispatch($counter);
        $game["currentJobId"] = $id;
        $date = date("Y-m-d H:i:s",strtotime('+1 hour'));
        $game["counterLastUpdated"] = $date;
        $game->update();
        $this->message = new WebSocketMessage("nextPlayer", $this->gameId, $players1["id"], $game["counterLastUpdated"], null, null);
        $this->delete();
        event(new MessageEvent($this->message));

    }
}
