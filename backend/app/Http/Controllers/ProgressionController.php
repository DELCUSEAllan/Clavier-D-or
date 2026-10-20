<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressionController extends Controller
{
    public function afficher(Request $request): JsonResponse
    {
        $progressions = $request->user()
            ->progressionsNiveaux()
            ->with('theme')
            ->get()
            ->sortBy(fn ($progression) => $progression->theme->numero_niveau)
            ->values()
            ->map(fn ($progression) => [
                'theme_id' => $progression->theme->id,
                'nom' => $progression->theme->nom,
                'numero_niveau' => $progression->theme->numero_niveau,
                'meilleur_score' => $progression->meilleur_score,
                'est_deverrouille' => $progression->est_deverrouille,
                'est_reussi' => $progression->reussi_le !== null,
                'reussi_le' => $progression->reussi_le,
            ]);

        return response()->json([
            'progression' => $progressions,
        ]);
    }
}
