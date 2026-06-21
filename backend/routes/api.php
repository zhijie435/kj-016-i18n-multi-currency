<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocaleController;

Route::prefix('api')->middleware(['api', \App\Http\Middleware\SetLocale::class])->group(function () {
    Route::get('/locale', [LocaleController::class, 'index']);
    Route::get('/locale/{locale}', [LocaleController::class, 'show']);
    Route::post('/locale', [LocaleController::class, 'update']);
});
