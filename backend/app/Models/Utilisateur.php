<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Utilisateur extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'utilisateurs';

    protected $fillable = [
        'pseudo',
        'email',
        'email_verifie_le',
        'mot_de_passe',
        'meilleur_score',
        'est_admin',
    ];

    protected $hidden = [
        'mot_de_passe',
        'jeton_souvenir',
    ];

    protected function casts(): array
    {
        return [
            'email_verifie_le' => 'datetime',
            'mot_de_passe' => 'hashed',
            'est_admin' => 'boolean',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'mot_de_passe';
    }

    public function getRememberTokenName(): string
    {
        return 'jeton_souvenir';
    }

    public function parties(): HasMany
    {
        return $this->hasMany(Partie::class, 'utilisateur_id');
    }

    public function progressionsNiveaux(): HasMany
    {
        return $this->hasMany(ProgressionNiveau::class);
    }
}
