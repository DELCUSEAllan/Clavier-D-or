<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Theme;
use Illuminate\Support\Collection;

class SelectionQuestionsService
{
    public function selectionner(Theme $theme, bool $estBoss): Collection
    {
        $repartition = $estBoss
            ? [
                'Facile' => 5,
                'Moyenne' => 5,
                'Difficile' => 5,
                'Extreme' => 5,
            ]
            : [
                'Facile' => 2,
                'Moyenne' => 2,
                'Difficile' => 2,
                'Extreme' => 1,
            ];

        $questions = collect();

        foreach ($repartition as $difficulte => $nombre) {
            $selection = Question::where('theme_id', $theme->id)
                ->where('difficulte', $difficulte)
                ->inRandomOrder()
                ->limit($nombre)
                ->get();

            $questions = $questions->concat($selection);
        }

        return $questions;
    }

    public function nombreAttendu(bool $estBoss): int
    {
        return $estBoss ? 20 : 7;
    }
}
