<?php

namespace App\Jobs;

use App\Events\MessageEvent;
use App\Models\WebSocketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateScore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

private $letters = "ABCDEFGHIJKLMNO";
private $reds = ["A1","A8","A15","H1","H15","O1","O8","O15"];
private $pinks = ["B2","B14","C3","C13","D4","D12","E5","E11","N2","N14","M3","M13","L4","L12","K5","K11"];
private $lightBlues = ["A4","A12","C7","C9","D1","D8","D15","G3","G7","G9","G13","H4","H12","I3","I7","I9","I13","L1","L8","L15","M7","M9","O4","O12"];
private $blues = ["B6","B10","F2","F6","F10","F14","J2","J6","J10","J14","N6","N10"];
private $lettersScore = array('A'=> 1,'B'=> 3,'C'=> 3,'D'=> 2,'E'=> 1,'F'=> 4,'G'=> 2,'H'=> 4,'I'=> 1,'J'=> 8,'K'=> 10,'L'=> 1,'M'=> 2,'N'=> 1,'O'=> 1,'P'=> 3,'Q'=> 8,'R'=> 1,'S'=> 1,'T'=> 1,'U'=> 1,'V'=> 4,'W'=> 10,'X'=> 10,'Y'=> 10,'Z'=> 10);
    protected $word;
    protected $newLetters;
    protected $indicesFormingOld;
    protected $nevWords;
    protected $indices;
    protected $direction;
    protected $positionH;
    protected $positionV;
    protected $game;
    protected $player;
    protected $wordMultiplier;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($word, $newLetters, $indicesFormingOld,$newWords, $indices, $direction, $positionH, $positionV, $game, $player)
    {
        $this->word = $word;
        $this->player = $player;
        $this->game = $game;
        $this->positionV = $positionV;
        $this->positionH = $positionH;
        $this->direction = $direction;
        $this->newLetters = $newLetters;
        $this->nevWords = $newWords;
        $this->indices = $indices;
        $this->indicesFormingOld = $indicesFormingOld;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $score = 0;
        $bingo = sizeof($this->newLetters) == 7;
        $newLets = "";
        for($i=0;$i<sizeof($this->newLetters);$i++) {
            $newLets.=$this->newLetters[$i];
        }
        $this->wordMultiplier = 1;
        $posH = intval($this->positionH);
        $posV = stripos($this->letters,$this->positionV);
        for($i=0;$i<strlen($this->word);$i++ ) {
            $sc = $this->lettersScore[strtoupper($this->word[$i])];
            if(stripos($newLets, $this->word[$i]) !== false) {
                $newLets = substr_replace($newLets, '', stripos($newLets, $this->word[$i]), 1);
                $posStr = $this->letters[$posV].strval($posH);
                $sc = $this->scoreForLetter($sc, $posStr);
            }
            $score+=$sc;
            if(strcasecmp($this->direction,"h") == 0) {
                $posH++;
            } else {
                $posV++;
            }
        }
        $score*= $this->wordMultiplier;


        $this->wordMultiplier = 1;

        $newLets ="";
        for($i=0;$i<sizeof($this->newLetters);$i++) {
            $newLets.=$this->newLetters[$i];
        }
        $x =0;
        $score2 = 0;
        foreach($this->nevWords as $w) {
            $sc = 0;
            $pV = preg_replace("/[^0-9]/", "", $this->indicesFormingOld[$x]);
            $pH = preg_replace('/[0-9]+/', '', $this->indicesFormingOld[$x]);
            if(strcasecmp($this->direction,"h") == 0) {
                $let = $this->word[$pV-$this->positionH];
            } else {
                $let = $this->word[stripos($this->letters, $pH) - stripos($this->letters, $this->positionV)];
            }

            $w = substr_replace($w, '', stripos($w, $let), 1);
            for($i=0;$i<strlen($w);$i++ ){
                $sc+=$this->lettersScore[strtoupper($w[$i])];
            }
            $sc+= $this->scoreForLetter($score2, $this->indicesFormingOld[$x++]);
            $sc*=$this->wordMultiplier;
            $score2+=$sc;
            $this->wordMultiplier = 1;
        }
        $score+=$score2;
        $this->player["score"]+=$score;
        $this->player->update();
        $this->message = new WebSocketMessage("score",$this->game["id"],$this->player["score"], $this->player["id"],null, null);
        event(new MessageEvent($this->message));
        if ($bingo){
            $this->player["score"]+=50;
            $this->player->update();
            sleep(3);
            $this->message = new WebSocketMessage("bingo",$this->game["id"],50, $this->player["id"],null, null);
            event(new MessageEvent($this->message));
        }
    }

    private function scoreForLetter($sc, $posStr): int
    {
            if(in_array($posStr, $this->reds)) {
                $this->wordMultiplier*=3;
            } else if(in_array($posStr, $this->pinks)) {
                $this->wordMultiplier*=2;
            } else if(in_array($posStr, $this->blues)) {
                $sc*=3;
            } else if(in_array($posStr, $this->lightBlues)) {
                $sc*=2;
            }
            return $sc;
        }
}
