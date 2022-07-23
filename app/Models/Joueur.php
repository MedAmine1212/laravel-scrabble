<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Joueur extends Model implements Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;

    public $timestamps = false;
    protected $fillable = ['nom', 'password', 'image', 'chevalet', 'score', 'statusJoueur', 'ordre', 'disconnected', 'voted', 'partieId'];

    public function partie()
    {
        return $this->belongsTo('App\Models\Partie');
    }

    public function messages()
    {
        return $this->hasMany('App\Models\Message');
    }

    public function findForPassport($username) {
        return $this->where('nom', $username)->first();
    }

    public function getAuthIdentifierName()
    {
        // TODO: Implement getAuthIdentifierName() method.
    }

    public function getAuthIdentifier()
    {
        // TODO: Implement getAuthIdentifier() method.
    }

    public function getAuthPassword()
    {
        // TODO: Implement getAuthPassword() method.
    }

    public function getRememberToken()
    {
        // TODO: Implement getRememberToken() method.
    }

    public function setRememberToken($value)
    {
        // TODO: Implement setRememberToken() method.
    }

    public function getRememberTokenName()
    {
        // TODO: Implement getRememberTokenName() method.
    }
}
