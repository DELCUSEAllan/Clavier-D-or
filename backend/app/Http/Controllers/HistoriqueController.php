<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoriqueController extends Controller
{
    public function afficher(Request $request): JsonResponse
    {
        $parties = $request->user()
            ->parties()
            ->whereIn('statut', ['perdue', 'gagnee'])
            ->orderByDesc('date_fin')
            ->get();

        $themes = Theme::pluck('nom', 'numero_niveau');

        $historique = $parties->map(function ($partie) use ($themes) {
            return [
                'partie_id' => $partie->id,
                'role' => $partie->role,
                'niveau_atteint' => $partie->niveau_actuel,
                'theme' => $themes->get($partie->niveau_actuel),
                'score_total' => $partie->score_total,
                'questions_correctes' => $partie->questions_correctes_total,
                'statut' => $partie->statut,
                'date_debut' => $partie->created_at,
                'date_fin' => $partie->date_fin,
            ];
        });

        return response()->json([
            'statistiques' => [
                'score_global' => (int)$request->user()->meilleur_score,
                'nombre_parties' => $parties->count(),
                'victoires' => $parties
                    ->where('statut', 'gagnee')
                    ->count(),
                'defaites' => $parties
                    ->where('statut', 'perdue')
                    ->count(),
                'meilleur_score_partie' => (int)(
                    $parties->max('score_total') ?? 0
                ),
                'niveau_maximal_atteint' => (int)(
                    $parties->max('niveau_actuel') ?? 0
                ),
            ],
            'historique' => $historique,
        ]);
    }
}
