<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\StatsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes API (JSON)
|--------------------------------------------------------------------------
| Ces routes exposent les données de l'agenda en JSON. Elles sont
| consommées par le front-end (fetch, voir resources/views) et
| utilisables avec Postman ou curl.
|
| Le middleware "web" est ajouté au groupe "api" pour conserver la
| session (cookie) et la protection CSRF : le front est servi par le
| même domaine que l'API, donc l'utilisateur est déjà connecté.
| Sans cela, chaque appel serait rejeté (401 "Unauthenticated").
|
| Les noms de routes sont préfixés par "api." pour ne pas entrer en
| conflit avec les ressources web (events.*, categories.*).
*/

Route::middleware(['web', 'auth'])->name('api.')->group(function () {
    Route::get('/stats', [StatsController::class, 'index'])->name('stats');

    Route::apiResource('events', EventController::class);

    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
});
