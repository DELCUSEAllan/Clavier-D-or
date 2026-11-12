<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModifierUtilisateurRequest;
use App\Models\Utilisateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UtilisateurController extends Controller
{
    public function afficher(): JsonResponse
    {
        $utilisateurs = Utilisateur::query()
            ->select([
                'id',
                'pseudo',
                'email',
                'email_verifie_le',
                'meilleur_score',
                'est_admin',
                'created_at',
            ])
            ->withCount('parties')
            ->orderBy('pseudo')
            ->paginate(20);

        return response()->json($utilisateurs);
    }

    public function modifier(ModifierUtilisateurRequest $request, Utilisateur $utilisateur): JsonResponse
    {
        $utilisateur->update($request->validated());

        $utilisateur->refresh();
        $utilisateur->loadCount('parties');

        return response()->json([
            'message' => 'Utilisateur modifié.',
            'utilisateur' => $utilisateur,
        ]);
    }

    public function supprimer(Request $request, Utilisateur $utilisateur): JsonResponse
    {
        $supprimeSonCompte = $request->user()->is($utilisateur);

        DB::transaction(function () use ($utilisateur): void {
            $utilisateur->tokens()->delete();

            DB::table('sessions')
                ->where('user_id', $utilisateur->id)
                ->delete();

            DB::table('password_reset_tokens')
                ->where('email', $utilisateur->email)
                ->delete();

            $utilisateur->delete();
        });

        if ($supprimeSonCompte && $request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'Utilisateur supprimé.',
        ]);
    }
}

