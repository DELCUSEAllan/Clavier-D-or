<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use Illuminate\Http\JsonResponse;

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
}
