<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'theme_id',
        'intitule',
        'type_question',
        'reponse_correcte',
        'reponse_fausse_1',
        'reponse_fausse_2',
        'reponse_fausse_3',
        'difficulte',
    ];

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function questionsPosees(): HasMany
    {
        return $this->hasMany(QuestionPosee::class);
    }
}
