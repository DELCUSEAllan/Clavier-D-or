<?php

namespace App\Http\Controllers;

use App\Models\Partie;
use App\Models\Question;
use App\Models\Theme;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


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

            'numero_niveau' => [
                'required',
                'integer',
                'between:1,6',
            ],
        ]);

        $numeroNiveau = (int)$donnees['numero_niveau'];
        $theme = Theme::where('numero_niveau', $numeroNiveau)->first();

        if (!$theme) {
            return response()->json([
                'message' => 'Le thème demandé est introuvable.',
            ], 422);
        }

        $progression = $request->user()
            ->progressionsNiveaux()
            ->where('theme_id', $theme->id)
            ->first();

        if (!$progression || !$progression->est_deverrouille) {
            return response()->json([
                'message' => 'Ce niveau est encore verrouillé.',
            ], 403);
        }

        $estBoss = $numeroNiveau === 6;
        $scoreMaximal = $estBoss ? 20000 : 7000;

        if ($progression->meilleur_score >= $scoreMaximal) {
            return response()->json([
                'message' => 'Tu as déjà obtenu le score maximal à ce niveau.',
            ], 403);
        }

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

        $nombreQuestionsAttendu = array_sum($repartition);
        if ($questions->count() !== $nombreQuestionsAttendu) {
            return response()->json([
                'message' => 'Il n’y a pas assez de questions pour démarrer le niveau.',
            ], 422);
        }

        $partie = $request->user()->parties()->create([
            'role' => $donnees['role'],
            'niveau_actuel' => $numeroNiveau,
            'erreurs_niveau' => 0,
            'bonnes_reponses_niveau' => 0,
            'jokers_restants' => $estBoss ? 3 : 1,
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
            'est_boss' => $estBoss,
        ], 201);
    }

    public function continuer(Request $request, Partie $partie): JsonResponse
    {
        if ($partie->utilisateur_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas accéder à cette partie.',
            ], 403);
        }

        if ($partie->statut !== 'niveau_reussi') {
            return response()->json([
                'message' => 'Le niveau actuel doit être réussi avant de continuer.',
            ], 422);
        }

        $numeroNiveauSuivant = $partie->niveau_actuel + 1;

        if ($numeroNiveauSuivant > 6) {
            return response()->json([
                'message' => 'Il n’y a plus de niveau suivant.',
            ], 422);
        }

        $theme = Theme::where(
            'numero_niveau',
            $numeroNiveauSuivant
        )->first();

        if (!$theme) {
            return response()->json([
                'message' => 'Le thème suivant est introuvable.',
            ], 422);
        }

        $progression = $request->user()
            ->progressionsNiveaux()
            ->where('theme_id', $theme->id)
            ->first();

        if (!$progression || !$progression->est_deverrouille) {
            return response()->json([
                'message' => 'Le niveau suivant est encore verrouillé.',
            ], 403);
        }

        $estBoss = $numeroNiveauSuivant === 6;

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

        $nombreQuestionsAttendu = array_sum($repartition);

        if ($questions->count() !== $nombreQuestionsAttendu) {
            return response()->json([
                'message' => 'Il n’y a pas assez de questions pour ce niveau.',
            ], 422);
        }

        $dernierOrdre = $partie->questionsPosees()
            ->max('ordre') ?? 0;

        foreach ($questions->values() as $index => $question) {
            $partie->questionsPosees()->create([
                'question_id' => $question->id,
                'ordre' => $dernierOrdre + $index + 1,
            ]);
        }

        $partie->update([
            'niveau_actuel' => $numeroNiveauSuivant,
            'erreurs_niveau' => 0,
            'bonnes_reponses_niveau' => 0,
            'jokers_restants' => $estBoss ? 3 : 1,
            'statut' => 'en_cours',
        ]);

        return response()->json([
            'message' => 'Niveau suivant démarré.',
            'partie' => $partie,
            'theme' => $theme->nom,
            'nombre_questions' => $questions->count(),
            'est_boss' => $estBoss,
        ]);
    }
}
