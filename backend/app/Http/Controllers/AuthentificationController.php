<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use App\Models\Utilisateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;


class AuthentificationController extends Controller
{
    private const DUREE_ACCES_MINUTES = 15;
    private const DUREE_ACTUALISATION_JOURS = 30;

    public function inscription(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'pseudo' => [
                'required',
                'string',
                'max:30',
                'unique:utilisateurs,pseudo',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:utilisateurs,email',
            ],

            'mot_de_passe' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $utilisateur = Utilisateur::create($donnees);

        $themes = Theme::orderBy('numero_niveau')->get();

        foreach ($themes as $theme) {
            $utilisateur->progressionsNiveaux()->create([
                'theme_id' => $theme->id,
                'meilleur_score' => 0,
                'est_deverrouille' => $theme->numero_niveau === 1,
            ]);
        }

        $jetons = $this->creerPaireJetons($utilisateur);

        return response()->json([
            'message' => 'Inscription réussie.',
            'utilisateur' => $utilisateur,
            'authentification' => $jetons,
        ], 201);
    }

        public function connexion(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'email' => [
                'required',
                'email',
            ],

            'mot_de_passe' => [
                'required',
                'string',
            ],
        ]);

        $utilisateur = Utilisateur::where('email', $donnees['email'])->first();

        if (
            !$utilisateur || !Hash::check($donnees['mot_de_passe'], $utilisateur->mot_de_passe)
        ) {
            return response()->json([
                'message' => 'Adresse email ou mot de passe incorrect.',
            ], 422);
        }

        $jetons = $this->creerPaireJetons($utilisateur);

        return response()->json([
            'message' => 'Connexion réussie.',
            'utilisateur' => $utilisateur,
            'authentification' => $jetons,
        ]);
    }

    public function rafraichir(Request $request): JsonResponse
    {
        $utilisateur = $request->user();
        $jetonActuel = $utilisateur->currentAccessToken();

        $estJetonActualisation = $jetonActuel instanceof PersonalAccessToken && str_starts_with($jetonActuel->name, 'refresh:') && in_array(
                'refresh',
                $jetonActuel->abilities ?? [],
                true
            );

        if (!$estJetonActualisation) {
            return response()->json([
                'message' => 'Jeton d’actualisation invalide.',
            ], 403);
        }

        $identifiantSession = Str::after($jetonActuel->name, 'refresh:');

        $jetons = $this->creerPaireJetons($utilisateur, $identifiantSession, true);

        return response()->json([
            'message' => 'Jetons actualisés.',
            'authentification' => $jetons,
        ]);
    }

    public function deconnexion(Request $request): JsonResponse
    {
        $utilisateur = $request->user();
        $jetonActuel = $utilisateur->currentAccessToken();

        if ($jetonActuel instanceof PersonalAccessToken) {
            if (str_contains($jetonActuel->name, ':')) {
                $identifiantSession = Str::after(
                    $jetonActuel->name,
                    ':'
                );

                $utilisateur->tokens()
                    ->whereIn('name', [
                        "access:$identifiantSession",
                        "refresh:$identifiantSession",
                    ])
                    ->delete();
            } else {
                $jetonActuel->delete();
            }
        }

        return response()->json([
            'message' => 'Déconnexion réussie.',
        ]);
    }

    private function creerPaireJetons(Utilisateur $utilisateur, ?string $identifiantSession = null, bool $remplacer = false): array
    {
        return DB::transaction(function () use ($utilisateur, $identifiantSession, $remplacer): array {
            $identifiantSession ??= (string)Str::uuid();

            if ($remplacer) {
                $utilisateur->tokens()
                    ->whereIn('name', [
                        "access:$identifiantSession",
                        "refresh:$identifiantSession",
                    ])
                    ->delete();
            }

            $expirationAcces = now()->addMinutes(self::DUREE_ACCES_MINUTES);

            $expirationActualisation = now()->addDays(self::DUREE_ACTUALISATION_JOURS);

            $jetonAcces = $utilisateur->createToken(
                "access:$identifiantSession",
                ['access'],
                $expirationAcces)->plainTextToken;

            $jetonActualisation = $utilisateur->createToken(
                "refresh:$identifiantSession",
                ['refresh'],
                $expirationActualisation)->plainTextToken;

            return [
                'type' => 'Bearer',
                'jeton_acces' => $jetonAcces,
                'jeton_actualisation' => $jetonActualisation,
                'expiration_acces' => $expirationAcces->toIso8601String(),
                'expiration_actualisation' => $expirationActualisation->toIso8601String(),
            ];
        });
    }
}

