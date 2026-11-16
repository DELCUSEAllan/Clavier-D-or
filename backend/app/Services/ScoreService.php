<?php

namespace App\Services;

use App\Models\Utilisateur;

class ScoreService
{
    public function synchroniserScoreGlobal(Utilisateur $utilisateur): int
    {
        $scoreGlobal = (int)$utilisateur
            ->progressionsNiveaux()
            ->sum('meilleur_score');

        if ((int)$utilisateur->meilleur_score !== $scoreGlobal) {
            $utilisateur->meilleur_score = $scoreGlobal;
            $utilisateur->save();
        }

        return $scoreGlobal;
    }
}
