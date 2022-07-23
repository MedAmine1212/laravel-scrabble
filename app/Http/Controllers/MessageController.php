<?php

namespace App\Http\Controllers;

use App\Events\MessageEvent;
use App\Jobs\Counter;
use App\Jobs\CalculateScore;
use App\Models\Joueur;
use App\Models\Message;
use App\Models\Partie;
use App\Models\WebSocketMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use function RingCentral\Psr7\str;

class MessageController extends Controller
{


    public $message;


    public function sendMessage($gameId, Request $request): JsonResponse
    {
        $msg =Message::create($request->all());
        $this->message = new WebSocketMessage("message",$gameId,0, $msg,null, null);
        event(new MessageEvent($this->message));
        return response()->json(["message"=>"success"], 200);
    }

    public function sendCommand($gameId, Request $request): JsonResponse
    {

        $player = Joueur::find($request->get("envoyeur"));
        if($request->get("contenu") != "!aide" && !$player["statusJoueur"]) {
            return response()->json(["message"=>"forbidden"], 403);
        }
        $msg =Message::create($request->all());
        $this->message = new WebSocketMessage("message",$gameId,0, $msg,null, null);
        event(new MessageEvent($this->message));
        $mg = "";
        if(strcasecmp($msg["contenu"], "!aide") == 0) {
            $mg = "<b>!placer <i>‹ligne›</i><i>‹colonne›</i>(h|v) ‹<i>mot</i>›</b> : Utiliser pour placer un mot sur le grille <br>(ex: !placer g15v bonjour)<br><hr>
                    <b>!changer ‹</i>lettre</i>›</b> : Utiliser pour changer un ou plusieur lettre (ex: !changer ebc* )<br><hr>
                    <b>!passer</b> : Utiliser pour passer le tour<br><hr>
                    <b>↹(Tab)</b>: Utiliser pour basculer entre la boite du communication et le chevalet<br><hr>
                    <b>cntrl+⇐|⇒(flèche droite et gauche du clavier)</b>: Utiliser pour boucler les commandes déjà envoyer<br><hr>
                    <b>Clic gauche de la souris</b>: Cliquez sur une case de la grille pour la <b>saisie rapide</b>";
            $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], null,$mg, null);
        event(new MessageEvent($this->message));

        } else if(preg_match('/!(placer) [a-oA-O]([1-9]|1[0-5])(v|h|V|H) [a-zA-Z]+/', $msg["contenu"], $output_array)) {
            $mg = "<b>Placement des lettres...</b>";
            $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], null,$mg, null);
            event(new MessageEvent($this->message));
            $game = Partie::find($gameId);
            //get command data
            $word = substr($msg["contenu"], strripos($msg["contenu"], " ")+1);
            $positionV = substr($msg["contenu"], strpos($msg["contenu"], " ")+1, 1);
            $positionH = substr($msg["contenu"], strpos($msg["contenu"], " ")+2, strlen($msg["contenu"])-(strlen($word)+11));
            $direction = substr($msg["contenu"], strpos($msg["contenu"], " ")+strlen($positionV)+strlen($positionH)+1, 1);
            $letters = "ABCDEFGHIJKLMNO";

            $incForNewWords = 1;
            if(strcasecmp("h", $direction) == 0) {
                $incForNewWords = 15;
            }
            //grid
            $grille = $game["grille"];
            //increment for letter placement
            $increment = 1;
            //intersection with other word
            $intersection = false;
            //atleast 1 new letter placed
            $newLetter = false;
            //if placed on vetical then change increment to 15

            //Positions of new letters
            $indices = [];
            $coordOfIndices = [];
            $indicesFormingOld = [];
            $newLetters = [];
            if(strcasecmp("v", $direction) == 0) {
                $increment = 15;
            }
            //check if word allready placed
            if(stripos($game["motsFormees"], " ".$word." ") !== false) {
                $mg = "<b>Mot déjà placé !</b>";
                $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], null,$mg, null);
                event(new MessageEvent($this->message));
                return response()->json(["message"=>"Mot déjà placé"], 200);
            }

            // checkin word direction
            if(strcasecmp($direction, 'v') == 0) {
                $index = stripos($letters, $positionV)+strlen($word)-1;
                if($index>strlen($letters)-1) {
                    //out of bounds vertical
                    $mg = "<b>Placement verticale illégal !</b>";
                    $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], null,$mg, null);
                    event(new MessageEvent($this->message));
                    return response()->json(["message"=>"Placement verticale illégal !"], 200);
                }
            } else {
                if($positionH+strlen($word)-1>15) {

                    //out of bounds horizontal
                    $mg = "<b>Placement horizontale illégal !</b>";
                    $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], null,$mg, null);
                    event(new MessageEvent($this->message));
                    return response()->json(["message"=>"Placement horizontale illégal !"], 200);
                }
            }

            //check if word placed between other letters
            if((intval($positionH) != 1 && strcasecmp($direction,"h") == 0) || (strcasecmp($direction,"v") == 0 && strcasecmp($positionV,"a") != 0)) {
            if($game["grille"][(15 * stripos($letters, $positionV) + intval($positionH) - 1) - $increment] != " ") {

                $mg = "<b>Placement illégal !</b>";
                $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], null,$mg, null);
                event(new MessageEvent($this->message));
                return response()->json(["message"=>"Placement illégal !"], 200);
            }
            }
            $nextInd = (15 * stripos($letters, $positionV) + intval($positionH) - 1) + $increment*strlen($word);
            if($nextInd < 225) {
            if ((intval($positionH)+strlen($word) != 15 && strcasecmp($direction,"h") == 0) || (strcasecmp($direction,"v") == 0 && strcasecmp($letters[stripos($letters, $positionV)+strlen($word)],"o") != 0))  {
                if($game["grille"][$nextInd] != " ") {
                    $mg = "<b>Placement illégal !</b>";
                    $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], null,$mg, null);
                    event(new MessageEvent($this->message));
                    return response()->json(["message"=>"Placement illégal !"], 200);
                }
            }
            }
            //check if grid is empty
            if(ctype_space($game["grille"])){
                for($x = 0;$x<strlen($word);$x++) {
                    //checking letters on wrack
                    if(stripos($player["chevalet"], $word[$x]) === false){
                        //special letter
                        if(stripos($player["chevalet"], " ") !== false) {
                            $player["chevalet"] = substr_replace($player["chevalet"], "", stripos($player["chevalet"], " "), 1);
                        } else {
                            // no letters
                            $mg = "<b>Placement illégal !</b> ";
                            $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], "noLetters",$mg, null);
                            event(new MessageEvent($this->message));
                            return response()->json(["message"=>"Placement illégal !"], 200);
                        }
                    } else {
                        // use letter when found
                        $player["chevalet"] = substr_replace($player["chevalet"], "", stripos($player["chevalet"], $word[$x]), 1);
                    }
                }
                //grid empty
                if(strcasecmp("H", $direction) == 0) {
                    //check letter on H8 horizontal
                    if(strcasecmp("H", $positionV)!=0) {
                        $mg = "<b>Premier placement illégal !</b>";
                        $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], null,$mg, null);
                        event(new MessageEvent($this->message));
                        return response()->json(["message"=>"Premier placement illégal !"], 200);
                    } else if(($positionH < 8 && ($positionH+strlen($word)-1) < 8) || $positionH > 8) {
                        $mg = "<b>Premier placement illégal !</b>";
                        $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], null,$mg, null);
                        event(new MessageEvent($this->message));
                        return response()->json(["message"=>"Premier placement illégal !"], 200);
                    }

                } else {
                    //check letter on H8 vertical
                    if($positionH != 8) {
                        $mg = "<b>Premier placement illégal !</b>";
                        $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], null,$mg, null);
                        event(new MessageEvent($this->message));
                        return response()->json(["message"=>"Premier placement illégal !"], 200);
                    }
                    if((stripos($letters,$positionV) < 7 && (stripos($letters,$positionV)+strlen($word)-1) < 7) || stripos($letters,$positionV) > 7) {
                        $mg = "<b>Premier placement illégal !</b>";
                        $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], null,$mg, null);
                        event(new MessageEvent($this->message));
                        return response()->json(["message"=>"Premier placement illégal !"], 200);
                    }
                }
            } else {
                //grid not empty and at least one intersection
                for($x = 0;$x<strlen($word);$x++) {
                    //test for touching other letters
                    $posForLetter =((15*stripos($letters,$positionV))+intval($positionH)+($increment*$x))-1;
                    if($posForLetter+$incForNewWords<225) {
                        if($game["grille"][$posForLetter+$incForNewWords] !=" ") {
                            $intersection = true;
                        }
                    }
                    if($posForLetter-$incForNewWords >0) {
                        if($game["grille"][$posForLetter-$incForNewWords] !=" ") {
                            $intersection = true;
                        }
                    }
                    //test for intersection with other words
                    if (strcasecmp($grille[(15 * stripos($letters, $positionV) + intval($positionH) - 1) + $x * $increment],$word[$x]) == 0) {
                        $intersection = true;
                    } else {
                        $newLetter = true;
                    }
                    if ($grille[(15 * stripos($letters, $positionV) + intval($positionH) - 1) + $x * $increment] != " " && strcasecmp($grille[(15 * stripos($letters, $positionV) + intval($positionH) - 1) + $x * $increment], $word[$x]) != 0) {
                        $mg = "<b>Une ou plusieurs cases sont déjà occupées !</b>";
                        $this->message = new WebSocketMessage("command", $gameId, $msg["envoyeur"], null, $mg, null);
                        event(new MessageEvent($this->message));
                        return response()->json(["message" => "Une ou plusieurs cases sont déjà occupées !"], 200);
                    } else  {

                        if(strcasecmp($grille[(15 * stripos($letters, $positionV) + intval($positionH) - 1) + $x * $increment], $word[$x]) != 0) {
                        //check if player has letters
                            if(stripos($player["chevalet"], $word[$x]) === false){

                                //special letter
                                if(stripos($player["chevalet"], " ") !== false) {
                                    $player["chevalet"] = substr_replace($player["chevalet"], "", stripos($player["chevalet"], " "), 1);
                                } else {
                                    // no letters
                                    $mg = "<b>Placement illégal !</b> ";
                                    $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], "noLetters",$mg, null);
                                    event(new MessageEvent($this->message));
                                    return response()->json(["message"=>"Placement illégal !"], 200);
                                }
                            } else {
                                // use letter when found
                                $player["chevalet"] = substr_replace($player["chevalet"], "", stripos($player["chevalet"], $word[$x]), 1);
                            }
                        }
                }

                }

                //if there's no intersection
                if(!$intersection) {
                    $mg = "<b>Il faut toucher au moins une lettre déjà placer !</b>";
                    $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], null,$mg, null);
                    event(new MessageEvent($this->message));
                    return response()->json(["message"=>"Il faut toucher au moins une lettre déjà placer !"], 200);
                }

                //if there are no new letters
                if(!$newLetter) {
                    $mg = "<b>Il faut placer au moins une nouvelle lettre!</b>";
                    $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], null,$mg, null);
                    event(new MessageEvent($this->message));
                    return response()->json(["message"=>"Il faut placer au moins une nouvelle lettre!"], 200);
                }
            }


                    //Placement successfull !

            $grille = $game["grille"];
            $y = 0;
            for ($x = 0; $x < strlen($word); $x++) {
                if (strcasecmp("H", $direction) == 0) {
                    $pos = (15*stripos($letters,$positionV))+(intval($positionH)+$x);
                } else {
                    $pos = (15*stripos($letters,$positionV))+intval($positionH)+(15*$x);
                }
                if($grille[$pos-1] == " ") {
                    $grille[$pos-1] = strtoupper($word[$x]);
                    $newLetters[$y] = $word[$x];
                    if(strcasecmp("H", $direction) == 0) {

                        $coordOfIndices[$y] = $positionV.strval(intval($positionH)+$x);
                    }else {
                        $coordOfIndices[$y] =  $letters[stripos($letters,$positionV)+$x].$positionH;
                    }
                    $indices[$y++] = $pos-1;
                }
            }
            $game["grille"] = $grille;

            $this->message = new WebSocketMessage("wordPlaced",$gameId,$msg["envoyeur"], $player['chevalet'],$grille, null);
            event(new MessageEvent($this->message));
            // french dictionnary
            $path = public_path().'/dictionnaire.txt';

            // english dictionnary
//            $path = public_path().'/englishDictionnary.txt';

            if (stripos(file_get_contents($path), '"'.$word.'"') === false) {
                //verify the new word
                $game = Partie::find($gameId);
                $grille = $game["grille"];
                $player = Joueur::find($msg["envoyeur"]);
                $this->message = new WebSocketMessage("invalid",$gameId,$msg["envoyeur"], $player['chevalet'],$grille, $word);
                event(new MessageEvent($this->message));

                sleep(1);
                (new Counter($game["id"], false, null))->updateTimer();

                $mg = "<b>Tour passé</b>";
                $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], null,$mg, null);
                event(new MessageEvent($this->message));
                return response()->json(["message"=>"Mot '".$word."' invalide !"], 200);

            } else {
                //find all new formed words

                $newWords = [];
                $y = 0;

                for($x=0;$x<sizeof($indices);$x++) {
                    $mot = "";
                    $pos =  $indices[$x];
                    //check letters before
                    while($pos>0) {
                        if($grille[$pos] ==' ') {
                            break;
                        } else {
                            $mot = $grille[$pos].$mot;
                        }
                        if($pos%15 == 0 && strcasecmp($direction, 'v') == 0) {
                            break;
                        }
                        $pos-=$incForNewWords;
                    }
                    $pos =  $indices[$x]+$incForNewWords;
                    //check letters after
                    while($pos<=224) {
                        if($grille[$pos] ==' ') {
                            break;
                        } else {
                            $mot .=$grille[$pos];
                        }
                        if($pos % 15 == 14 && strcasecmp($direction, 'v') == 0) {
                            break;
                        }
                        $pos+=$incForNewWords;
                    }
                    if(strlen($mot)>1) {
                    $indicesFormingOld[$y] = $coordOfIndices[$x];
                    $newWords[$y++] = $mot;
                    }
                }
            }
            //check the new words formed
                foreach($newWords as $w) {
                    if (stripos(file_get_contents($path), '"'.$w.'"') === false) {
                        $game = Partie::find($gameId);
                        $grille = $game["grille"];
                        $player = Joueur::find($msg["envoyeur"]);
                        $this->message = new WebSocketMessage("invalid",$gameId,$msg["envoyeur"], $player['chevalet'],$grille, $w);
                        event(new MessageEvent($this->message));

                        sleep(1);
                        (new Counter($game["id"], false, null))->updateTimer();

                        $mg = "<b>Tour passé</b>";
                        $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], null,$mg, null);
                        event(new MessageEvent($this->message));
                        return response()->json(["message"=>"Mot '".$w."' invalide !"], 200);

                    } else {
                        if(strlen($game["motsFormees"]) == 0) {
                            $game["motsFormees"] = $game["motsFormees"]." ";
                        }
                        $game["motsFormees"] = $game["motsFormees"].$w." ";
                    }
                }
            $reserve = $game["reserve"];

            if(strlen($reserve) > 7-strlen($player["chevalet"])) {
                $length = strlen($player["chevalet"]);
                for ($x = 0; $x < 7-$length; $x++) {
                    $pos = rand(0, strlen($reserve) - 1);
                    $player['chevalet'] = $player['chevalet'].$reserve[$pos];
                    $reserve = substr_replace($reserve, '', $pos, 1);
                }
            } else {
                $player['chevalet'] = $player['chevalet'].$reserve;
                $reserve = "";
            }
            $game["reserve"] = $reserve;
            if(strlen($game["motsFormees"]) == 0) {
                $game["motsFormees"] = $game["motsFormees"]." ";
            }
            $game["motsFormees"] = $game["motsFormees"].$word." ";
            $player->update();
            $game["currentJobId"] = "empty";
            $game->update();
            $this->message = new WebSocketMessage("valid",$gameId,$msg["envoyeur"], $player['chevalet'],$grille, null);
            event(new MessageEvent($this->message));
            // Refresh player timer
            sleep(1);
            (new Counter($game["id"], false, null))->updateTimer();
            $mg = "<b>Tour passé</b>";
            $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], null,$mg, null);
            event(new MessageEvent($this->message));

            // Score calculation
            CalculateScore::dispatch($word, $newLetters, $indicesFormingOld ,$newWords, $indices, $direction, $positionH, $positionV, $game, $player);
            if(strlen($player["chevalet"]) == 0 && strlen($game["reserve"]) == 0) {
                sleep(2);
                $game["statusPartie"] ="Finie";
                $game->update();
                $message = new WebSocketMessage("gameEnded", $game["id"],null,null, null, null);
                event(new MessageEvent($message));
            }
            return response()->json(["message"=>"Mots valides !"], 200);
        } else if (strcasecmp($msg["contenu"], '!passer') == 0) {

            (new Counter($gameId, false, null))->updateTimer();

            $mg = "<b>Tour passé</b>";
            $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], null,$mg, null);
            event(new MessageEvent($this->message));
        }else if(preg_match('/!(changer) [a-zA-Z*]+$/', $msg["contenu"], $output_array)) {
            $removedLetters ="";
            $player = Joueur::find($msg["envoyeur"]);
            $game = Partie::find($gameId);
            $letters = substr($msg["contenu"], strripos($msg["contenu"], " ")+1);
            $letters = str_replace('*', ' ', $letters);
            if(strlen($game["reserve"])<strlen($letters)) {
                $mg = "<b>Lettres dans reserve insuffisant !</b>";
                $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], null,$mg, null);
                event(new MessageEvent($this->message));
                return response()->json(["message"=>"Lettres dans reserve insuffisant !"], 200);
            } else {
                for($x=0;$x<strlen($letters);$x++) {
                    if(stripos($player["chevalet"], $letters[$x]) === false) {
                        $mg = "<b>Lettres invalides !</b>";
                        $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], "noLetter",$mg, $letters[$x]);
                        event(new MessageEvent($this->message));
                        return response()->json(["message"=>"Lettres dans reserve insuffisant !"], 200);
                    } else {
                        $removedLetters.=$letters[$x];
                        $player["chevalet"] = substr_replace($player["chevalet"], "", stripos($player["chevalet"], $letters[$x]), 1);
                    }
                }
                for($x=0;$x<strlen($removedLetters);$x++) {
                    $pos = rand(0, strlen($game["reserve"]) - 1);
                    $player['chevalet'] = $player['chevalet'].$game["reserve"][$pos];
                    $game["reserve"] = substr_replace($game["reserve"], '', $pos, 1);
                }
                $game["reserve"].=strtoupper($removedLetters);
                $player->update();
                $game->update();
                $this->message = new WebSocketMessage("replaced",$gameId,$msg["envoyeur"], null,null, $player["chevalet"]);
                event(new MessageEvent($this->message));
                sleep(1);
                (new Counter($gameId, false, null))->updateTimer();
                return response()->json(["message"=>"Lettres changer !"], 200);
            }
        } else {
            $mg = "Erreur de syntaxe, tapez <b><i>!aide</i></b>";
            $this->message = new WebSocketMessage("command",$gameId,$msg["envoyeur"], null,$mg, null);
            event(new MessageEvent($this->message));
            return response()->json(["message"=>"Erreur de syntaxe ! taper !aide"], 200);
        }

        return response()->json(["message"=>"success"], 200);
    }

    public function getAllByGame($gameId): JsonResponse
    {
        $messages =Message::where('partie', $gameId)->orderBy('dateCreation')->get();
        foreach ($messages as $message){
            $js = Joueur::where('id', $message["envoyeur"])->get();
            foreach ($js as $j)
                $message["envoyeur"] = $j;
        }
        return response()->json($messages, 200);
    }

}
