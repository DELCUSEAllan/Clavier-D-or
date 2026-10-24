<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partie;
use App\Models\Question;
use App\Models\Utilisateur;
use Illuminate\Http\JsonResponse;

class TableauDeBordController extends Controller
{
    public function afficher(): JsonResponse
    {
        return response()->json([
            'message' => 'Accès administrateur autorisé.',

            'statistiques' => [
                'joueurs' => Utilisateur::where(
                    'est_admin',
                    false
                )->count(),

                'administrateurs' => Utilisateur::where(
                    'est_admin',
                    true
                )->count(),

                'questions' => Question::count(),

                'parties' => Partie::count(),

                'parties_en_cours' => Partie::whereIn(
                    'statut',
                    ['en_cours', 'niveau_reussi']
                )->count(),

                'victoires' => Partie::where(
                    'statut',
                    'gagnee'
                )->count(),

                'defaites' => Partie::where(
                    'statut',
                    'perdue'
                )->count(),
            ],
        ]);
    }
}
