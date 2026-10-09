<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressionNiveau extends Model
{
    protected $table = 'progression_niveaux';

    protected $fillable = [
        'utilisateur_id',
        'theme_id',
        'meilleur_score',
        'est_deverrouille',
        'reussi_le',
    ];

    protected function casts(): array
    {
        return [
            'est_deverrouille' => 'boolean',
            'reussi_le' => 'datetime',
        ];
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class);
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
