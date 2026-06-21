<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ChannelController;

Route::prefix('api')->middleware(['api', \App\Http\Middleware\SetLocale::class])->group(function () {
    Route::get('/locale', [LocaleController::class, 'index']);
    Route::get('/locale/{locale}', [LocaleController::class, 'show']);
    Route::post('/locale', [LocaleController::class, 'update']);
    Route::get('/locales/all', [LocaleController::class, 'all']);
    Route::post('/locales', [LocaleController::class, 'store']);
    Route::put('/locales/{id}', [LocaleController::class, 'updateLocale']);
    Route::delete('/locales/{id}', [LocaleController::class, 'destroy']);

    Route::get('/channels', [ChannelController::class, 'index']);
    Route::get('/channels/enabled', [ChannelController::class, 'enabled']);
    Route::get('/channels/{id}', [ChannelController::class, 'show']);
    Route::post('/channels', [ChannelController::class, 'store']);
    Route::put('/channels/{id}', [ChannelController::class, 'update']);
    Route::put('/channels/{id}/locale', [ChannelController::class, 'updateLocale']);
    Route::delete('/channels/{id}', [ChannelController::class, 'destroy']);
    Route::get('/channels/{channelCode}/locale', [ChannelController::class, 'getChannelLocale']);
});
