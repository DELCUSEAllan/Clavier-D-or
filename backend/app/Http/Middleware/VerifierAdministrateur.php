<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifierAdministrateur
{
    public function handle(
        Request $request,
        Closure $next
    ): Response|JsonResponse {
        if (!$request->user()?->est_admin) {
            return response()->json([
                'message' => 'Accès réservé aux administrateurs.',
            ], 403);
        }

        return $next($request);
    }
}
