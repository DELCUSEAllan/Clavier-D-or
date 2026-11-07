<?php

use App\Http\Controllers\AuthentificationController;
use App\Http\Controllers\PartieController;
use App\Http\Controllers\ProgressionController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\JokerController;
use App\Http\Controllers\HistoriqueController;
use App\Http\Controllers\BilanPdfController;
use App\Http\Controllers\Admin\TableauDeBordController;

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
    QuestionController::class,
    'questionSuivante'])
    ->middleware('auth:sanctum');

Route::get('/progression', [
    ProgressionController::class,
    'afficher',
])->middleware('auth:sanctum');

Route::post(
    '/parties/{partie}/repondre',
    [QuestionController::class, 'repondre']
)->middleware('auth:sanctum');

Route::post(
    '/parties/{partie}/continuer',
    [PartieController::class, 'continuer']
)->middleware('auth:sanctum');

Route::patch(
    '/parties/{partie}/abandonner',
    [PartieController::class, 'abandonner']
)->middleware('auth:sanctum');

Route::post('/parties/{partie}/joker', [
    JokerController::class,
    'utiliserJoker',
])->middleware('auth:sanctum');

Route::get('/parties/actuelle', [
    PartieController::class,
    'actuelle',
])->middleware('auth:sanctum');

Route::get('/historique', [
    HistoriqueController::class,
    'afficher',
])->middleware('auth:sanctum');

Route::get('/bilan/pdf', [
    BilanPdfController::class,
    'telecharger',
])->middleware('auth:sanctum');

Route::middleware([
    'auth:sanctum',
    'admin',
])->prefix('admin')->group(function () {
    Route::get('/tableau-de-bord', [
        TableauDeBordController::class,
        'afficher',
    ]);
});
