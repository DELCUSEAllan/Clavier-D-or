<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    protected $fillable = [
        'nom',
        'numero_niveau',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
