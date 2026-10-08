<?php

use App\Http\Controllers\AuthentificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/inscription', [
    AuthentificationController::class,
    'inscription',
]);

Route::get('/utilisateur', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/connexion', [
    AuthentificationController::class,
    'connexion',
]);

Route::post('/deconnexion', [
    AuthentificationController::class,
    'deconnexion',
])->middleware('auth:sanctum');
