<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = ['dateCreation', 'contenu', 'statusMessage', 'partie', 'envoyeur'];

    public function partie()
    {
        return $this->belongsTo('App\Models\Partie');
    }

    public function joueur()
    {
        return $this->belongsTo('App\Models\Joueur');
    }
}
