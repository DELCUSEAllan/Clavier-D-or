<?php

namespace App\Http\Controllers;

use App\Models\Partie;
use App\Models\Theme;
use App\Models\Utilisateur;
use App\Services\SelectionQuestionsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PartieController extends Controller
{
    public function __construct(
        private readonly SelectionQuestionsService $selectionQuestionsService
    ) {
    }

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

        return DB::transaction(function () use ($request, $donnees): JsonResponse {

            $utilisateur = Utilisateur::query()
                ->whereKey($request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();

            $partieActive = $utilisateur
                ->parties()
                ->whereIn(
                    'statut',
                    ['en_cours', 'niveau_reussi']
                )
                ->latest('id')
                ->first();

            if ($partieActive) {
                return response()->json([
                    'message' => 'Une partie est déjà en cours.',
                    'partie' => $partieActive,
                    'action_attendue' => $partieActive->statut === 'niveau_reussi' ? 'continuer' : 'reprendre',
                ], 409);
            }

            $numeroNiveau =
                (int)$donnees['numero_niveau'];

            $theme = Theme::where(
                'numero_niveau',
                $numeroNiveau
            )->first();

            if (!$theme) {
                return response()->json([
                    'message' => 'Le thème demandé est introuvable.',
                ], 422);
            }

            $progression = $utilisateur
                ->progressionsNiveaux()
                ->where('theme_id', $theme->id)
                ->lockForUpdate()
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
                    'message' =>
                        'Tu as déjà obtenu le score maximal à ce niveau.',
                ], 403);
            }

            $questions = $this
                ->selectionQuestionsService
                ->selectionner($theme, $estBoss);

            $nombreQuestionsAttendu = $this
                ->selectionQuestionsService
                ->nombreAttendu($estBoss);

            if ($questions->count() !== $nombreQuestionsAttendu) {
                return response()->json([
                    'message' =>
                        'Il n’y a pas assez de questions pour démarrer le niveau.',
                ], 422);
            }

            $partie = $utilisateur
                ->parties()
                ->create([
                    'role' => $donnees['role'],
                    'niveau_actuel' => $numeroNiveau,
                    'erreurs_niveau' => 0,
                    'bonnes_reponses_niveau' => 0,
                    'jokers_restants' => $estBoss ? 3 : 1,
                    'score_total' => 0,
                    'questions_correctes_total' => 0,
                    'statut' => 'en_cours',
                ]);

            $questionsPosees = $partie
                ->questionsPosees()
                ->createMany(
                    $questions
                        ->values()
                        ->map(
                            fn($question, int $index): array => [
                                'question_id' =>
                                    $question->id,
                                'ordre' => $index + 1,
                            ]
                        )
                        ->all()
                );

            if ($questionsPosees->count() !== $nombreQuestionsAttendu
            ) {
                throw new RuntimeException(
                    'La création des questions de la partie a échoué.'
                );
            }

            return response()->json([
                'message' => 'Partie démarrée.',
                'partie' => $partie,
                'theme' => $theme->nom,
                'nombre_questions' => $questionsPosees->count(),
                'est_boss' => $estBoss,
            ], 201);
        }, 5);
    }

    public function continuer(Request $request, Partie $partie): JsonResponse
    {
        return DB::transaction(function () use ($request, $partie): JsonResponse {
            $partieVerrouillee = Partie::query()
                ->whereKey($partie->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($partieVerrouillee->utilisateur_id !== $request->user()->id) {
                return response()->json([
                    'message' => 'Vous ne pouvez pas accéder à cette partie.',
                ], 403);
            }

            if ($partieVerrouillee->statut !== 'niveau_reussi'
            ) {
                return response()->json([
                    'message' => 'Le niveau actuel doit être réussi avant de continuer.',
                ], 422);
            }

            $numeroNiveauSuivant = $partieVerrouillee->niveau_actuel + 1;

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

            $progression = $request
                ->user()
                ->progressionsNiveaux()
                ->where('theme_id', $theme->id)
                ->lockForUpdate()
                ->first();

            if (!$progression || !$progression->est_deverrouille
            ) {
                return response()->json([
                    'message' => 'Le niveau suivant est encore verrouillé.',
                ], 403);
            }

            $estBoss = $numeroNiveauSuivant === 6;

            $questions = $this
                ->selectionQuestionsService
                ->selectionner($theme, $estBoss);

            $nombreQuestionsAttendu = $this
                ->selectionQuestionsService
                ->nombreAttendu($estBoss);

            if ($questions->count() !== $nombreQuestionsAttendu) {
                return response()->json([
                    'message' => 'Il n’y a pas assez de questions pour ce niveau.',
                ], 422);
            }

            $dernierOrdre = $partieVerrouillee
                ->questionsPosees()
                ->max('ordre') ?? 0;

            $questionsPosees = $partieVerrouillee
                ->questionsPosees()
                ->createMany(
                    $questions
                        ->values()
                        ->map(
                            fn($question, int $index): array => [
                                'question_id' => $question->id,
                                'ordre' => $dernierOrdre + $index + 1,
                            ]
                        )
                        ->all()
                );

            if ($questionsPosees->count() !== $nombreQuestionsAttendu) {
                throw new RuntimeException(
                    'La création des questions du niveau a échoué.'
                );
            }

            $partieVerrouillee->update([
                'niveau_actuel' => $numeroNiveauSuivant,
                'erreurs_niveau' => 0,
                'bonnes_reponses_niveau' => 0,
                'jokers_restants' => $estBoss ? 3 : 1,
                'statut' => 'en_cours',
                'date_fin' => null,
            ]);

            return response()->json([
                'message' => 'Niveau suivant démarré.',
                'partie' => $partieVerrouillee,
                'theme' => $theme->nom,
                'nombre_questions' => $questionsPosees->count(),
                'est_boss' => $estBoss,
            ]);
        }, 5);
    }

    public function abandonner(Request $request, Partie $partie): JsonResponse
    {
        return DB::transaction(function () use ($request, $partie): JsonResponse {
            $partieVerrouillee = Partie::query()
                ->whereKey($partie->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($partieVerrouillee->utilisateur_id !== $request->user()->id) {
                return response()->json([
                    'message' => 'Vous ne pouvez pas abandonner cette partie.',
                ], 403);
            }

            if (
                !in_array(
                    $partieVerrouillee->statut,
                    ['en_cours', 'niveau_reussi'],
                    true
                )
            ) {
                return response()->json([
                    'message' => 'Cette partie ne peut plus être abandonnée.',
                ], 422);
            }

            $partieVerrouillee->update([
                'statut' => 'abandonnee',
                'date_fin' => now(),
            ]);

            return response()->json([
                'message' => 'Partie abandonnée.',
                'partie' => $partieVerrouillee,
            ]);
        }, 5);
    }

    public function actuelle(Request $request): JsonResponse
    {
        $partie = $request
            ->user()
            ->parties()
            ->whereIn(
                'statut',
                ['en_cours', 'niveau_reussi']
            )
            ->latest('id')
            ->first();

        if (!$partie) {
            return response()->json([
                'message' => 'Aucune partie en cours.',
                'partie' => null,
            ]);
        }

        $theme = Theme::where(
            'numero_niveau',
            $partie->niveau_actuel
        )->first();

        return response()->json([
            'partie' => $partie,
            'theme' => $theme?->nom,
            'action_attendue' =>
                $partie->statut === 'niveau_reussi' ? 'continuer' : 'reprendre',
        ]);
    }
}
