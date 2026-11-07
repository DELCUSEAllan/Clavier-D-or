<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partie extends Model
{
    protected $fillable = [
        'utilisateur_id',
        'role',
        'niveau_actuel',
        'erreurs_niveau',
        'bonnes_reponses_niveau',
        'jokers_restants',
        'score_total',
        'questions_correctes_total',
        'statut',
        'date_fin',
    ];

    protected $hidden = [
        'verrou_partie_active',
    ];

    protected function casts(): array
    {
        return [
            'date_fin' => 'datetime',
        ];
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class);
    }

    public function questionsPosees(): HasMany
    {
        return $this->hasMany(QuestionPosee::class);
    }
}
