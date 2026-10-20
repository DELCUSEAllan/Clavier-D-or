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
        $seuilReussite = $estBoss ? 15 : 5;

        $defaiteImmediate = $partie->erreurs_niveau >= $limiteErreurs;

        $aucuneQuestionRestante = !$partie->questionsPosees()
            ->whereNull('est_correcte')
            ->exists();

        $niveauTermine = $defaiteImmediate || $aucuneQuestionRestante;
        $niveauReussi = null;
        $mention = null;
        $meilleurScoreNiveau = null;
        $scoreNiveau = $partie->bonnes_reponses_niveau * 1000;

        if ($niveauTermine) {
            $niveauReussi = !$defaiteImmediate
                && $partie->bonnes_reponses_niveau >= $seuilReussite;

            $theme = Theme::where(
                'numero_niveau',
                $partie->niveau_actuel
            )->first();

            $progression = $partie->utilisateur
                ->progressionsNiveaux()
                ->where('theme_id', $theme->id)
                ->first();

            if ($progression) {
                $progression->meilleur_score = max(
                    $progression->meilleur_score,
                    $scoreNiveau
                );

                if ($niveauReussi && $progression->reussi_le === null) {
                    $progression->reussi_le = now();
                }

                $progression->save();
                $meilleurScoreNiveau = $progression->meilleur_score;
            }

            if (!$niveauReussi) {
                $partie->statut = 'perdue';
                $partie->date_fin = now();

                $mention = $estBoss
                    ? 'Le boss t’a vaincu. Retente ta chance plus tard.'
                    : 'Niveau échoué. Retente ta chance plus tard.';
            } elseif ($estBoss) {
                $partie->statut = 'gagnee';
                $partie->date_fin = now();

                $mention = $scoreNiveau === 20000
                    ? 'Bravo, tu as vaincu le boss avec un score parfait !'
                    : 'Tu as vaincu le boss et remporté le Clavier d’Or !';
            } else {
                $partie->statut = 'niveau_reussi';

                $mention = match ($partie->bonnes_reponses_niveau) {
                    7 => 'Bravo, tu as passé ce niveau aisément.',
                    6 => 'Bien joué, tu t’en sors bien !',
                    default => 'Tu as eu chaud, tu as passé le niveau tout juste.',
                };

                $themeSuivant = Theme::where(
                    'numero_niveau',
                    $partie->niveau_actuel + 1
                )->first();

                if ($themeSuivant) {
                    $partie->utilisateur
                        ->progressionsNiveaux()
                        ->where('theme_id', $themeSuivant->id)
                        ->update([
                            'est_deverrouille' => true,
                        ]);
                }
            }

            $partie->save();
        }

        $message = $mention ?? (
        $estCorrecte
            ? 'Bonne réponse.'
            : 'Mauvaise réponse.'
        );

        return response()->json([
            'message' => $message,
            'est_correcte' => $estCorrecte,
            'reponse_correcte' => $questionPosee->question->reponse_correcte,
            'points_gagnes' => $estCorrecte ? 1000 : 0,
            'bonnes_reponses_niveau' => $partie->bonnes_reponses_niveau,
            'erreurs_niveau' => $partie->erreurs_niveau,
            'score_total' => $partie->score_total,
            'niveau_termine' => $niveauTermine,
            'niveau_reussi' => $niveauReussi,
            'mention' => $mention,
            'score_niveau' => $scoreNiveau,
            'meilleur_score_niveau' => $meilleurScoreNiveau,
            'statut_partie' => $partie->statut,
        ]);
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

        if ($partie->role !== 'mobile') {
            return response()->json([
                'message' => 'Cette partie ne possède pas le joker mobile.',
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
}
