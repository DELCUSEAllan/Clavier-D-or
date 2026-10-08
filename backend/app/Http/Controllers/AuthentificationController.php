<?php

namespace App\Http\Controllers;

use App\Models\Utilisateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthentificationController extends Controller
{
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

        $jeton = $utilisateur
            ->createToken('jeton-authentification')
            ->plainTextToken;

        return response()->json([
            'message' => 'Inscription réussie.',
            'utilisateur' => $utilisateur,
            'jeton' => $jeton,
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

        $jeton = $utilisateur
            ->createToken('jeton-authentification')
            ->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie.',
            'utilisateur' => $utilisateur,
            'jeton' => $jeton,
        ]);
    }

        public function deconnexion(Request $request): JsonResponse
    {
        $request->user()
            ->currentAccessToken()
            ->delete();

        return response()->json([
            'message' => 'Déconnexion réussie.',
        ]);
    }
}

