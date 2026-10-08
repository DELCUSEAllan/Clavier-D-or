<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use App\Models\Question;
use App\Models\Theme;

class PartieController extends Controller
{
    public function demarrer(Request $request): JsonResponse
{
    $donnees = $request->validate([
        'role' => [
            'required',
            'string',
            'in:front,back,mobile',
        ],
    ]);

    $theme = Theme::where('numero_niveau', 1)->first();

    if (!$theme) {
        return response()->json([
            'message' => 'Le thème du niveau 1 est introuvable.',
        ], 422);
    }

    $repartition = [
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

    if ($questions->count() !== 7) {
        return response()->json([
            'message' => 'Il n’y a pas assez de questions pour démarrer le niveau.',
        ], 422);
    }

    $partie = $request->user()->parties()->create([
        'role' => $donnees['role'],
        'niveau_actuel' => 1,
        'erreurs_niveau' => 0,
        'bonnes_reponses_niveau' => 0,
        'jokers_restants' => 1,
        'score_total' => 0,
        'questions_correctes_total' => 0,
        'statut' => 'en_cours',
    ]);

    foreach ($questions->values() as $index => $question) {
        $partie->questionsPosees()->create([
            'question_id' => $question->id,
            'ordre' => $index + 1,
        ]);
    }

    return response()->json([
        'message' => 'Partie démarrée.',
        'partie' => $partie,
        'theme' => $theme->nom,
        'nombre_questions' => $questions->count(),
    ], 201);
    }
}
