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
