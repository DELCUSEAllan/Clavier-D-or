<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EnregistrerThemeRequest;
use App\Models\Theme;
use App\Models\Utilisateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ThemeController extends Controller
{
    public function afficher(): JsonResponse
    {
        $themes = Theme::query()
            ->withCount([
                'questions',
                'progressionsNiveaux',
            ])
            ->orderBy('numero_niveau')
            ->get();

        return response()->json([
            'themes' => $themes,
        ]);
    }

    public function creer(
        EnregistrerThemeRequest $request): JsonResponse
    {
        $donnees = $request->validated();

        $theme = DB::transaction(function () use ($donnees): Theme {
            $theme = Theme::create($donnees);

            Utilisateur::query()->eachById(
                function (Utilisateur $utilisateur) use ($theme): void {
                    $utilisateur->progressionsNiveaux()->create([
                        'theme_id' => $theme->id,
                        'meilleur_score' => 0,
                        'est_deverrouille' =>
                            (int)$theme->numero_niveau === 1,
                    ]);
                }
            );

            return $theme;
        });

        return response()->json([
            'message' => 'Thème créé.',
            'theme' => $theme->loadCount([
                'questions',
                'progressionsNiveaux',
            ]),
        ], 201);
    }

    public function modifier(EnregistrerThemeRequest $request, Theme $theme): JsonResponse
    {
        DB::transaction(function () use ($request, $theme): void {
            $theme->update($request->validated());

            if ((int)$theme->numero_niveau === 1) {
                $theme->progressionsNiveaux()->update([
                    'est_deverrouille' => true,
                ]);
            }
        });

        $theme->refresh();
        $theme->loadCount([
            'questions',
            'progressionsNiveaux',
        ]);

        return response()->json([
            'message' => 'Thème modifié.',
            'theme' => $theme,
        ]);
    }

    public function supprimer(Theme $theme): JsonResponse
    {
        $nombreQuestions = $theme->questions()
            ->withTrashed()
            ->count();

        $nombreProgressions = $theme->progressionsNiveaux()
            ->count();

        DB::transaction(function () use ($theme): void {
            $theme->delete();
        });

        return response()->json([
            'message' => 'Thème supprimé.',
            'questions_supprimees' => $nombreQuestions,
            'progressions_supprimees' => $nombreProgressions,
        ]);
    }
}
