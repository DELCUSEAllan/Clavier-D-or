<?php

use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\Admin\TableauDeBordController;
use App\Http\Controllers\Admin\UtilisateurController;
use App\Http\Controllers\AuthentificationController;
use App\Http\Controllers\BilanPdfController;
use App\Http\Controllers\HistoriqueController;
use App\Http\Controllers\JokerController;
use App\Http\Controllers\PartieController;
use App\Http\Controllers\ProgressionController;
use App\Http\Controllers\QuestionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/inscription', [
    AuthentificationController::class,
    'inscription'])
    ->middleware('throttle:3,1');

Route::get('/utilisateur', function (Request $request) {
    return $request->user();
})->middleware([
    'auth:sanctum',
    'abilities:access',
]);

Route::post('/connexion', [
    AuthentificationController::class,'connexion',])
    ->middleware('throttle:5,1');

Route::post('/jetons/rafraichir', [
    AuthentificationController::class,
    'rafraichir',
])->middleware([
    'auth:sanctum',
    'abilities:refresh',
]);

Route::post('/deconnexion', [
    AuthentificationController::class,
    'deconnexion',])
    ->middleware([
        'auth:sanctum',
        'abilities:access',
    ]);

Route::post('/parties/demarrer', [
    PartieController::class,
    'demarrer',])
    ->middleware([
        'auth:sanctum',
        'abilities:access',
    ]);

Route::get(
    '/parties/{partie}/question-suivante',[
    QuestionController::class,
    'questionSuivante'])
    ->middleware([
        'auth:sanctum',
        'abilities:access',
    ]);

Route::get('/progression', [
    ProgressionController::class,
    'afficher',
])->middleware([
    'auth:sanctum',
    'abilities:access',
]);

Route::post(
    '/parties/{partie}/repondre',
    [QuestionController::class, 'repondre']
)->middleware([
    'auth:sanctum',
    'abilities:access',
]);

Route::post(
    '/parties/{partie}/continuer',
    [PartieController::class, 'continuer']
)->middleware([
    'auth:sanctum',
    'abilities:access',
]);

Route::patch(
    '/parties/{partie}/abandonner',
    [PartieController::class, 'abandonner']
)->middleware([
    'auth:sanctum',
    'abilities:access',
]);

Route::post('/parties/{partie}/joker', [
    JokerController::class,
    'utiliserJoker',
])->middleware([
    'auth:sanctum',
    'abilities:access',
]);

Route::get('/parties/actuelle', [
    PartieController::class,
    'actuelle',
])->middleware([
    'auth:sanctum',
    'abilities:access',
]);

Route::get('/historique', [
    HistoriqueController::class,
    'afficher',
])->middleware([
    'auth:sanctum',
    'abilities:access',
]);

Route::get('/bilan/pdf', [
    BilanPdfController::class,
    'telecharger',
])->middleware([
    'auth:sanctum',
    'abilities:access',
]);

Route::middleware([
    'auth:sanctum',
    'abilities:access',
    'admin',
])->prefix('admin')->group(function () {
    Route::get('/tableau-de-bord', [
        TableauDeBordController::class,
        'afficher',
    ]);

    Route::get('/utilisateurs', [
        UtilisateurController::class,
        'afficher',
    ]);

    Route::get('/questions', [
        AdminQuestionController::class,
        'afficher',
    ]);

    Route::post('/questions', [
        AdminQuestionController::class,
        'creer',
    ]);

    Route::put('/questions/{question}', [
        AdminQuestionController::class,
        'modifier',
    ]);

    Route::delete('/questions/{question}', [
        AdminQuestionController::class,
        'supprimer',
    ]);
});
