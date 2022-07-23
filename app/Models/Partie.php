<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partie extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = ['typePartie', 'reserve', 'grille', 'motsFormees', 'dateCreation', 'dateDebutPartie', 'dateFinPartie', 'statusPartie', 'currentJobId', 'counterLastUpdated', 'tempsJoueur', 'endVotes', 'joueurs'];

    public function joueurs(){
        return $this->hasMany('App\Models\Joueur');
    }

    public function messages(){
        return $this->hasMany('App\Models\Message');
    }
}
