<?php

namespace App\Http\Controllers;

use App\Models\Partie;
use App\Models\Question;
use App\Models\QuestionPosee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JokerController extends Controller
{
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

    private function utiliserJokerFront(Partie $partie,QuestionPosee $questionPosee): JsonResponse {
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
