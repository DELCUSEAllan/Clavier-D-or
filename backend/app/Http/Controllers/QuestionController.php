<?php

namespace App\Http\Controllers;

use App\Models\Partie;
use App\Models\Theme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
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
        return DB::transaction(function () use ($request, $partie): JsonResponse {

            $partieVerrouillee = Partie::query()
                ->whereKey($partie->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($partieVerrouillee->utilisateur_id !== $request->user()->id) {
                return response()->json([
                    'message' =>
                        'Vous ne pouvez pas accéder à cette partie.',
                ], 403);
            }

            if ($partieVerrouillee->statut !== 'en_cours') {
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

            $questionPosee = $partieVerrouillee
                ->questionsPosees()
                ->whereKey($donnees['question_posee_id'])
                ->with('question')
                ->lockForUpdate()
                ->first();

            if (!$questionPosee) {
                return response()->json([
                    'message' =>
                        'Cette question n’appartient pas à la partie.',
                ], 404);
            }

            if ($questionPosee->est_correcte !== null) {
                return response()->json([
                    'message' =>
                        'Cette question a déjà été répondue.',
                ], 422);
            }

            $reponseDonnee = trim($donnees['reponse']);

            $estCorrecte = $reponseDonnee === trim(
                    $questionPosee->question->reponse_correcte
                );

            $premiereErreurProtegee = !$estCorrecte
                && $partieVerrouillee->role === 'back'
                && $questionPosee->joker_utilise
                && !$questionPosee->rattrapage_utilise;

            if ($premiereErreurProtegee) {
                $questionPosee->update([
                    'reponse_donnee' => $reponseDonnee,
                    'rattrapage_utilise' => true,
                ]);

                return response()->json([
                    'message' =>
                        'Mauvaise réponse, mais le joker back vous accorde une seconde tentative ',
                    'est_correcte' => false,
                    'rattrapage_autorise' => true,
                    'question_posee_id' => $questionPosee->id,
                    'points_gagnes' => 0,
                    'erreurs_niveau' => $partieVerrouillee->erreurs_niveau,
                    'score_total' => $partieVerrouillee->score_total,
                    'statut_partie' => $partieVerrouillee->statut,
                ]);
            }

            $questionPosee->update([
                'reponse_donnee' => $reponseDonnee,
                'est_correcte' => $estCorrecte,
            ]);

            if ($estCorrecte) {
                $partieVerrouillee->bonnes_reponses_niveau++;
                $partieVerrouillee->questions_correctes_total++;
                $partieVerrouillee->score_total += 1000;

            } else {
                $partieVerrouillee->erreurs_niveau++;
            }

            $partieVerrouillee->save();

            $estBoss = $partieVerrouillee->niveau_actuel === 6;
            $limiteErreurs = $estBoss ? 6 : 3;
            $seuilReussite = $estBoss ? 15 : 5;

            $defaiteImmediate =
                $partieVerrouillee->erreurs_niveau
                >= $limiteErreurs;

            $aucuneQuestionRestante = !$partieVerrouillee
                ->questionsPosees()
                ->whereNull('est_correcte')
                ->exists();

            $niveauTermine =
                $defaiteImmediate || $aucuneQuestionRestante;

            $niveauReussi = null;
            $mention = null;
            $meilleurScoreNiveau = null;

            $scoreNiveau =
                $partieVerrouillee->bonnes_reponses_niveau
                * 1000;

            $utilisateur = $partieVerrouillee
                ->utilisateur()
                ->lockForUpdate()
                ->firstOrFail();

            $scoreGlobal = (int)$utilisateur->meilleur_score;

            if ($niveauTermine) {
                $niveauReussi = !$defaiteImmediate
                    && $partieVerrouillee
                        ->bonnes_reponses_niveau
                    >= $seuilReussite;

                $theme = Theme::where(
                    'numero_niveau',
                    $partieVerrouillee->niveau_actuel
                )->firstOrFail();

                $progression = $utilisateur
                    ->progressionsNiveaux()
                    ->where('theme_id', $theme->id)
                    ->lockForUpdate()
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

                    $meilleurScoreNiveau =
                        $progression->meilleur_score;

                    $scoreGlobal = (int)$utilisateur
                        ->progressionsNiveaux()
                        ->sum('meilleur_score');

                    $utilisateur->meilleur_score = $scoreGlobal;
                    $utilisateur->save();
                }

                if (!$niveauReussi) {
                    $partieVerrouillee->statut = 'perdue';
                    $partieVerrouillee->date_fin = now();

                    $mention = $estBoss
                        ? 'Le boss t’a vaincu. Retente ta chance plus tard.'
                        : 'Niveau échoué. Retente ta chance plus tard.';

                } elseif ($estBoss) {
                    $partieVerrouillee->statut = 'gagnee';
                    $partieVerrouillee->date_fin = now();

                    $mention = $scoreNiveau === 20000
                        ? 'Bravo, tu as vaincu le boss avec un score parfait '
                        : 'Tu as vaincu le boss et remporté le Clavier d’Or';

                } else {
                    $partieVerrouillee->statut =
                        'niveau_reussi';

                    $mention = match (
                    $partieVerrouillee
                        ->bonnes_reponses_niveau
                    ) {
                        7 => 'Bravo, tu as passé ce niveau aisément.',
                        6 => 'Bien joué, tu t’en sors bien !',
                        default => 'Tu as eu chaud, tu as passé le niveau tout juste.',
                    };

                    $themeSuivant = Theme::where(
                        'numero_niveau',
                        $partieVerrouillee->niveau_actuel + 1
                    )->first();

                    if ($themeSuivant) {
                        $utilisateur
                            ->progressionsNiveaux()
                            ->where('theme_id', $themeSuivant->id)
                            ->update([
                                'est_deverrouille' => true,
                            ]);
                    }
                }

                $partieVerrouillee->save();
            }

            $message = $mention ?? (
            $estCorrecte ? 'Bonne réponse.' : 'Mauvaise réponse.');

            return response()->json([
                'message' => $message,
                'est_correcte' => $estCorrecte,
                'reponse_correcte' => $questionPosee->question->reponse_correcte,
                'points_gagnes' => $estCorrecte ? 1000 : 0,
                'bonnes_reponses_niveau' => $partieVerrouillee->bonnes_reponses_niveau,
                'erreurs_niveau' => $partieVerrouillee->erreurs_niveau,
                'score_total' => $partieVerrouillee->score_total,
                'score_global' => $scoreGlobal,
                'niveau_termine' => $niveauTermine,
                'niveau_reussi' => $niveauReussi,
                'mention' => $mention,
                'score_niveau' => $scoreNiveau,
                'meilleur_score_niveau' => $meilleurScoreNiveau,
                'statut_partie' => $partieVerrouillee->statut,
            ]);
        }, 5);
    }
}
