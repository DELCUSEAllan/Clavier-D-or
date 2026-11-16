<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BilanPdfController extends Controller
{
    public function telecharger(Request $request)
    {
        $utilisateur = $request->user();

        $progressions = $utilisateur
            ->progressionsNiveaux()
            ->with('theme')
            ->get()
            ->sortBy(fn($progression) => $progression->theme->numero_niveau)
            ->values();

        $scoreGlobal = (int) $request->user()->meilleur_score;

        $tropheePlatine = $progressions->count() === 6
            && $progressions->every(function ($progression): bool {
                $scoreMaximal =
                    (int)$progression->theme->numero_niveau === 6
                        ? 20000
                        : 7000;

                return $progression->meilleur_score >= $scoreMaximal;
            });

        $requeteParties = $utilisateur
            ->parties()
            ->whereIn('statut', ['perdue', 'gagnee']);

        $statistiquesParties = (clone $requeteParties)
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
            ->first();

        $statistiques = [
            'nombre_parties' => (int)$statistiquesParties->nombre_parties,
            'victoires' => (int)$statistiquesParties->victoires,
            'defaites' => (int)$statistiquesParties->defaites,
            'meilleur_score_partie' => (int)$statistiquesParties->meilleur_score_partie,
        ];

        $limiteHistorique = 50;

        $parties = (clone $requeteParties)
            ->select([
                'id',
                'role',
                'niveau_actuel',
                'score_total',
                'statut',
                'date_fin',
            ])
            ->orderByDesc('date_fin')
            ->limit($limiteHistorique)
            ->get();

        $historiqueTronque =
            $statistiques['nombre_parties'] > $parties->count();

        $themes = Theme::pluck('nom', 'numero_niveau');

        $pdf = Pdf::loadView('pdf.bilan', [
            'utilisateur' => $utilisateur,
            'progressions' => $progressions,
            'scoreGlobal' => $scoreGlobal,
            'tropheePlatine' => $tropheePlatine,
            'parties' => $parties,
            'themes' => $themes,
            'statistiques' => $statistiques,
            'limiteHistorique' => $limiteHistorique,
            'historiqueTronque' => $historiqueTronque,
            'dateGeneration' => now(),

        ])->setPaper('a4', 'portrait');

        $nomFichier = 'bilan-clavier-dor-'
            . Str::slug($utilisateur->pseudo)
            . '.pdf';

        return $pdf->download($nomFichier);
    }
}
