<?php

use App\Http\Controllers\AuthentificationController;
use App\Http\Controllers\PartieController;
use App\Http\Controllers\ProgressionController;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Route;


Route::post('/inscription', [
    AuthentificationController::class,
    'inscription'])
    ->middleware('throttle:3,1');

Route::get('/utilisateur', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/connexion', [
    AuthentificationController::class,'connexion',])
    ->middleware('throttle:5,1');

Route::post('/deconnexion', [
    AuthentificationController::class,
    'deconnexion',])
    ->middleware('auth:sanctum');

Route::post('/parties/demarrer', [
    PartieController::class,
    'demarrer',])
    ->middleware('auth:sanctum');

Route::get(
    '/parties/{partie}/question-suivante',[
    PartieController::class,
    'questionSuivante'])
    ->middleware('auth:sanctum');

Route::get('/progression', [
    ProgressionController::class,
    'afficher',
])->middleware('auth:sanctum');
