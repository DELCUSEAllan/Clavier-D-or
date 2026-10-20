<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionPosee extends Model
{
    protected $table = 'questions_posees';

    protected $fillable = [
        'partie_id',
        'question_id',
        'ordre',
        'reponse_donnee',
        'est_correcte',
        'joker_utilise',
        'est_remplacee_par_joker',
    ];

    protected function casts(): array
    {
        return [
            'est_correcte' => 'boolean',
            'joker_utilise' => 'boolean',
            'est_remplacee_par_joker' => 'boolean',
        ];
    }

    public function partie(): BelongsTo
    {
        return $this->belongsTo(Partie::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
