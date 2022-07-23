<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebSocketMessage
{
    public $type;
    public $gameId;
    public $count;
    public $message;
    public $msg;
    public $word;

    public function __construct($type,$gameId,$count,$message, $msg, $word)
    {
        $this->type = $type;
        $this->gameId = $gameId;
        $this->count = $count;
        $this->message = $message;
        $this->msg = $msg;
        $this->word = $word;
    }
}
