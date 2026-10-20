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


    public function questionSuivante(Request $request, Partie $partie): JsonResponse
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
            ->filter(fn($reponse) => $reponse !== null)
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
    public function repondre(Request $request, Partie $partie): JsonResponse
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

        $donnees = $request->validate([
            'question_posee_id' => [
                'required',
                'integer',
            ],
            'reponse' => [
                'required',
                'string',
                'max:500',
            ],
        ]);

        $questionPosee = $partie->questionsPosees()
            ->with('question')
            ->find($donnees['question_posee_id']);

        if (!$questionPosee) {
            return response()->json([
                'message' => 'Cette question n’appartient pas à la partie.',
            ], 404);
        }

        if ($questionPosee->est_correcte !== null) {
            return response()->json([
                'message' => 'Cette question a déjà été répondue.',
            ], 422);
        }

        $reponseDonnee = trim($donnees['reponse']);

        $estCorrecte = $reponseDonnee ===
            trim($questionPosee->question->reponse_correcte);

        $questionPosee->update([
            'reponse_donnee' => $reponseDonnee,
            'est_correcte' => $estCorrecte,
        ]);

        if ($estCorrecte) {
            $partie->bonnes_reponses_niveau++;
            $partie->questions_correctes_total++;
            $partie->score_total += 1000;
        } else {
            $partie->erreurs_niveau++;
        }

        $partie->save();

        $estBoss = $partie->niveau_actuel === 6;
        $limiteErreurs = $estBoss ? 6 : 3;

        $partieTerminee = $partie->erreurs_niveau >= $limiteErreurs;

        if ($partieTerminee) {
            $partie->statut = 'perdue';
            $partie->date_fin = now();
            $partie->save();
        }

        $message = $estCorrecte
            ? 'Bonne réponse.'
            : 'Mauvaise réponse.';

        if ($partieTerminee) {
            $message = $estBoss
                ? 'Le boss t’a vaincu. Retente ta chance plus tard.'
                : 'Niveau échoué. Retente ta chance plus tard.';
        }

        return response()->json([
            'message' => $message,
            'est_correcte' => $estCorrecte,
            'reponse_correcte' => $questionPosee->question->reponse_correcte,
            'points_gagnes' => $estCorrecte ? 1000 : 0,
            'bonnes_reponses_niveau' => $partie->bonnes_reponses_niveau,
            'erreurs_niveau' => $partie->erreurs_niveau,
            'score_total' => $partie->score_total,
            'partie_terminee' => $partieTerminee,
            'statut_partie' => $partie->statut,
        ]);
    }
}
