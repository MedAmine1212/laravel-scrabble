<?php

namespace App\Jobs;

use App\Events\MessageEvent;
use App\Events\StatusEvent;
use App\Models\Joueur;
use App\Models\WebSocketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Pusher\PusherException;

class CheckUserStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */

    protected $id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws PusherException
     */
    public function handle()
    {

        $player = Joueur::find($this->id);
        if (!is_null($player)) {
            if($player["disconnected"]){
                return;
            }
            $dt =date("Y-m-d H:i:s",strtotime('+1 hour'));
            error_log(strtotime($dt)*1000-strtotime($player["pingedAt"])*1000);
            if(strtotime($dt)*1000-strtotime($player["pingedAt"])*1000 > 5000) {
                $dt = $player["pingedAt"];
                sleep(1);
                $player = Joueur::find($this->id);
                if($player["pingedAt"] == $dt) {
                $player["disconnected"] = true;
                $player->update();
                event(new MessageEvent(new WebSocketMessage("playerLeft",$player["partieId"],$this->id, null, null, null)));

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
                }
            }
            }
    }
}
