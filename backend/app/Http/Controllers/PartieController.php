<?php

namespace App\Http\Controllers;

use App\Models\Partie;
use App\Models\Question;
use App\Models\QuestionPosee;
use App\Models\Theme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    public function utiliserJoker(Request $request, Partie $partie): JsonResponse
    {
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

        if (!in_array($partie->role, ['mobile', 'front', 'back'], true)) {
            return response()->json([
                'message' => 'Le joker de cette partie n’est pas encore disponible.',
            ], 422);
        }

        $questionPosee = $partie->questionsPosees()
            ->whereNull('est_correcte')
            ->with('question')
            ->orderBy('ordre')
            ->first();

        if (!$questionPosee) {
            return response()->json([
                'message' => 'Il n’y a plus de question en attente.',
            ], 422);
        }

        if ($questionPosee->joker_utilise) {
            return response()->json([
                'message' => 'Un joker a déjà été utilisé sur cette question.',
            ], 422);
        }

        if ($partie->jokers_restants <= 0) {
            return response()->json([
                'message' => 'Vous n’avez plus de joker disponible.',
            ], 422);
        }

        if ($partie->role === 'back') {
            $questionPosee->update([
                'joker_utilise' => true,
            ]);

            $partie->decrement('jokers_restants');
            $partie->refresh();

            return response()->json([
                'message' => 'Le joker back est activé pour cette question.',
                'protection_active' => true,
                'jokers_restants' => $partie->jokers_restants,
            ]);
        }

        if ($partie->role === 'front') {
            return $this->utiliserJokerFront($partie, $questionPosee);
        }

        $bonneReponse = trim($questionPosee->question->reponse_correcte);

        $premiereLettre = mb_strtoupper(
            mb_substr($bonneReponse, 0, 1)
        );

        $questionPosee->update([
            'joker_utilise' => true,
        ]);

        $partie->decrement('jokers_restants');
        $partie->refresh();

        return response()->json([
            'message' => 'Le joker mobile a été utilisé.',
            'premiere_lettre' => $premiereLettre,
            'jokers_restants' => $partie->jokers_restants,
        ]);
    }

    private function utiliserJokerFront(
        Partie        $partie,
        QuestionPosee $questionPosee
    ): JsonResponse
    {
        $questionActuelle = $questionPosee->question;

        $identifiantsDejaUtilises = $partie->questionsPosees()
            ->pluck('question_id');

        $nouvelleQuestion = Question::where(
            'theme_id',
            $questionActuelle->theme_id
        )
            ->where('difficulte', $questionActuelle->difficulte)
            ->whereNotIn('id', $identifiantsDejaUtilises)
            ->inRandomOrder()
            ->first();

        if (!$nouvelleQuestion) {
            return response()->json([
                'message' => 'Aucune autre question de cette difficulté n’est disponible.',
            ], 422);
        }

        $ordreActuel = $questionPosee->ordre;

        $ordreHistorique = ((int)$partie->questionsPosees()
                ->max('ordre')) + 1;

        $nouvelleQuestionPosee = DB::transaction(function () use (
            $partie,
            $questionPosee,
            $nouvelleQuestion,
            $ordreActuel,
            $ordreHistorique
        ) {
            $questionPosee->update([
                'ordre' => $ordreHistorique,
                'est_correcte' => false,
                'joker_utilise' => true,
                'est_remplacee_par_joker' => true,
            ]);

            $nouvelleQuestionPosee = $partie->questionsPosees()->create([
                'question_id' => $nouvelleQuestion->id,
                'ordre' => $ordreActuel,
            ]);

            $partie->decrement('jokers_restants');

            return $nouvelleQuestionPosee;
        });

        $partie->refresh();

        $propositions = collect([
            $nouvelleQuestion->reponse_correcte,
            $nouvelleQuestion->reponse_fausse_1,
            $nouvelleQuestion->reponse_fausse_2,
            $nouvelleQuestion->reponse_fausse_3,
        ])
            ->filter(fn($reponse) => $reponse !== null)
            ->shuffle()
            ->values();

        return response()->json([
            'message' => 'La question a été remplacée.',
            'question' => [
                'question_posee_id' => $nouvelleQuestionPosee->id,
                'ordre' => $nouvelleQuestionPosee->ordre,
                'intitule' => $nouvelleQuestion->intitule,
                'type_question' => $nouvelleQuestion->type_question,
                'difficulte' => $nouvelleQuestion->difficulte,
                'propositions' => $propositions,
            ],
            'jokers_restants' => $partie->jokers_restants,
        ]);
    }
}
