<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\ExchangeRateController;

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
    Route::get('/channels/{channelCode}/currency', [ChannelController::class, 'getChannelCurrency']);

    Route::get('/currencies', [CurrencyController::class, 'index']);
    Route::get('/currencies/enabled', [CurrencyController::class, 'enabled']);
    Route::get('/currencies/{code}', [CurrencyController::class, 'show']);
    Route::post('/currencies', [CurrencyController::class, 'store']);
    Route::put('/currencies/{id}', [CurrencyController::class, 'update']);
    Route::delete('/currencies/{id}', [CurrencyController::class, 'destroy']);

    Route::get('/exchange-rates', [ExchangeRateController::class, 'index']);
    Route::get('/exchange-rates/active', [ExchangeRateController::class, 'active']);
    Route::get('/exchange-rates/rate', [ExchangeRateController::class, 'getRate']);
    Route::get('/exchange-rates/convert', [ExchangeRateController::class, 'convert']);
    Route::post('/exchange-rates/matrix', [ExchangeRateController::class, 'matrix']);
    Route::get('/exchange-rates/{id}', [ExchangeRateController::class, 'show']);
    Route::post('/exchange-rates', [ExchangeRateController::class, 'store']);
    Route::put('/exchange-rates/{id}', [ExchangeRateController::class, 'update']);
    Route::delete('/exchange-rates/{id}', [ExchangeRateController::class, 'destroy']);
    Route::post('/exchange-rates/{id}/activate', [ExchangeRateController::class, 'activate']);
    Route::post('/exchange-rates/{id}/deactivate', [ExchangeRateController::class, 'deactivate']);
});
