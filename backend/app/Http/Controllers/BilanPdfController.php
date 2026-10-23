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
            ->sortBy(fn($progression) => $progression->theme->numero_niveau
            )
            ->values();

        $scoreGlobal = (int)$progressions->sum('meilleur_score');

        $tropheePlatine = $progressions->count() === 6
            && $progressions->every(function ($progression): bool {
                $scoreMaximal =
                    (int)$progression->theme->numero_niveau === 6
                        ? 20000
                        : 7000;

                return $progression->meilleur_score >= $scoreMaximal;
            });

        $parties = $utilisateur
            ->parties()
            ->whereIn('statut', ['perdue', 'gagnee'])
            ->orderByDesc('date_fin')
            ->get();

        $themes = Theme::pluck('nom', 'numero_niveau');

        $statistiques = [
            'nombre_parties' => $parties->count(),
            'victoires' => $parties->where('statut', 'gagnee')->count(),
            'defaites' => $parties->where('statut', 'perdue')->count(),
            'meilleur_score_partie' => (int)(
                $parties->max('score_total') ?? 0
            ),
        ];

        $pdf = Pdf::loadView('pdf.bilan', [
            'utilisateur' => $utilisateur,
            'progressions' => $progressions,
            'scoreGlobal' => $scoreGlobal,
            'tropheePlatine' => $tropheePlatine,
            'parties' => $parties,
            'themes' => $themes,
            'statistiques' => $statistiques,
            'dateGeneration' => now(),
        ])->setPaper('a4', 'portrait');

        $nomFichier = 'bilan-clavier-dor-'
            . Str::slug($utilisateur->pseudo)
            . '.pdf';

        return $pdf->download($nomFichier);
    }
}
