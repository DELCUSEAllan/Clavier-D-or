<?php

namespace App\Http\Controllers;

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
        ]);

        $partie = $request->user()->parties()->create([
            'role' => $donnees['role'],
            'niveau_actuel' => 1,
            'erreurs_niveau' => 0,
            'bonnes_reponses_niveau' => 0,
            'jokers_restants' => 1,
            'score_total' => 0,
            'questions_correctes_total' => 0,
            'statut' => 'en_cours',
        ]);

        return response()->json([
            'message' => 'Partie démarrée.',
            'partie' => $partie,
        ], 201);
    }
}
