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
        'rattrapage_utilise',
        'contenu_fige'
    ];

    protected $hidden = ['contenu_fige'];

    protected function casts(): array
    {
        return [
            'est_correcte' => 'boolean',
            'joker_utilise' => 'boolean',
            'est_remplacee_par_joker' => 'boolean',
            'rattrapage_utilise' => 'boolean',
            'contenu_fige' => 'array'
        ];
    }

    public static function figer(Question $question): array
    {
        return $question->only([
            'theme_id',
            'intitule',
            'type_question',
            'difficulte',
            'reponse_correcte',
            'reponse_fausse_1',
            'reponse_fausse_2',
            'reponse_fausse_3',
        ]);
    }

    public function partie(): BelongsTo
    {
        return $this->belongsTo(Partie::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class)->withTrashed();
    }
}
