<?php

namespace App\Jobs;

use App\Events\MessageEvent;
use App\Models\Joueur;
use App\Models\WebSocketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DisconnectPlayer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $id;
    protected $pingedAt;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $pingedAt)
    {
        $this->id = $id;
        $this->pingedAt = $pingedAt;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        error_log($this->id);
        $player = Joueur::find($this->id);
        if($this->pingedAt == $player["pingedAt"]) {
            $partieId = $player["partieId"];
            $player["partieId"] = null;
            $player["disconnected"] = false;
            $player["score"] = 0;
            $player["chevalet"] = "";
            $player["statusJoueur"] = false;
            $player["voted"] = false;
            $player["ordre"] = null;
            $player["pingedAt"] = null;
            $player->update();
            $message = new WebSocketMessage("playerDisconnected", $partieId, $player["id"],null, null, null);
            event(new MessageEvent($message));
        }
        $this->delete();
    }
}
