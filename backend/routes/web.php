<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthentificationController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api/web')->group(function () {
    Route::post('/connexion', [
        AuthentificationController::class,
        'connexionWeb',
    ])->middleware('throttle:5,1');

    Route::post('/deconnexion', [
        AuthentificationController::class,
        'deconnexionWeb',
    ])->middleware('auth');
});

    Route::get('/mot-de-passe/reinitialiser/{token}', function (string $token) {
        return view('auth.reinitialiser', ['token' => $token]);
    })->name('password.reset');

    Route::post('/mot-de-passe/reinitialiser', [
        AuthentificationController::class,
        'reinitialiserMotDePasse',
    ])->middleware('throttle:5,1')->name('password.update');

    Route::get('/mot-de-passe/modifie', function () {
        return 'Mot de passe modifié. Tu peux te reconnecter dans l’application.';
    })->name('password.done');
