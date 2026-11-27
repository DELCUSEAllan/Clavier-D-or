<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoriqueController extends Controller
{
    public function afficher(Request $request): JsonResponse
    {
        $requete = $request->user()
            ->parties()
            ->whereIn('statut', ['perdue', 'gagnee', 'abandonnee']);

        $statistiquesParties = (clone $requete)
            ->selectRaw('COUNT(*) AS nombre_parties')
            ->selectRaw("
            COALESCE(
                SUM(CASE WHEN statut = 'gagnee' THEN 1 ELSE 0 END),
                0
            ) AS victoires
        ")
            ->selectRaw("
            COALESCE(
                SUM(CASE WHEN statut = 'perdue' THEN 1 ELSE 0 END),
                0
            ) AS defaites
        ")
            ->selectRaw("
            COALESCE(MAX(score_total), 0)
            AS meilleur_score_partie
        ")
            ->selectRaw("
            COALESCE(MAX(niveau_actuel), 0)
            AS niveau_maximal_atteint
        ")
            ->first();

        $parties = (clone $requete)
            ->select([
                'id',
                'role',
                'niveau_actuel',
                'score_total',
                'questions_correctes_total',
                'statut',
                'created_at',
                'date_fin',
            ])
            ->orderByDesc('date_fin')
            ->paginate(20);

        $themes = Theme::pluck('nom', 'numero_niveau');

        $historique = $parties
            ->getCollection()
            ->map(function ($partie) use ($themes): array {
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
                'nombre_parties' => (int)$statistiquesParties->nombre_parties,
                'victoires' => (int)$statistiquesParties->victoires,
                'defaites' => (int)$statistiquesParties->defaites,
                'meilleur_score_partie' => (int)$statistiquesParties->meilleur_score_partie,
                'niveau_maximal_atteint' => (int)$statistiquesParties->niveau_maximal_atteint,
            ],

            'historique' => $historique,

            'pagination' => [
                'page_actuelle' => $parties->currentPage(),
                'derniere_page' => $parties->lastPage(),
                'par_page' => $parties->perPage(),
                'total' => $parties->total(),
            ],
        ]);
    }
}
