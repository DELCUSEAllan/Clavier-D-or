<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use App\Models\Question;
use App\Models\Theme;
use App\Models\Partie;

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


    public function questionSuivante(Request $request,Partie $partie): JsonResponse {
    if ($partie->utilisateur_id !== $request->user()->id) {
        return response()->json([
            'message' => 'Vous ne pouvez pas accéder à cette partie.',
        ], 403);
    }

    if ($partie->statut !== 'en_cours') {
        return response()->json([
            'message' => 'Cette partie n’est plus en cours.',
        ], 422);
    }

    $questionPosee = $partie->questionsPosees()
        ->whereNull('est_correcte')
        ->with('question')
        ->orderBy('ordre')
        ->first();

    if (!$questionPosee) {
        return response()->json([
            'message' => 'Toutes les questions de cette tentative ont été répondues.',
            'terminee' => true,
        ]);
    }

    $question = $questionPosee->question;

    $propositions = collect([
        $question->reponse_correcte,
        $question->reponse_fausse_1,
        $question->reponse_fausse_2,
        $question->reponse_fausse_3,
    ])
        ->filter(fn ($reponse) => $reponse !== null)
        ->shuffle()
        ->values();

    return response()->json([
        'terminee' => false,

        'question' => [
            'question_posee_id' => $questionPosee->id,
            'ordre' => $questionPosee->ordre,
            'intitule' => $question->intitule,
            'type_question' => $question->type_question,
            'difficulte' => $question->difficulte,
            'propositions' => $propositions,
            ],
        ]);
    }
}
