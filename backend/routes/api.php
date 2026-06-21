<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\ExchangeRateController;

Route::middleware(['api', 'setlocale'])->group(function () {

    Route::middleware(['permission:locale.view'])->group(function () {
        Route::get('/locale', [LocaleController::class, 'index']);
        Route::get('/locale/{locale}', [LocaleController::class, 'show']);
        Route::post('/locale', [LocaleController::class, 'update']);
        Route::get('/locales/all', [LocaleController::class, 'all']);
    });

    Route::middleware(['permission:locale.create'])->group(function () {
        Route::post('/locales', [LocaleController::class, 'store']);
    });

    Route::middleware(['permission:locale.update'])->group(function () {
        Route::put('/locales/{id}', [LocaleController::class, 'updateLocale']);
    });

    Route::middleware(['permission:locale.delete'])->group(function () {
        Route::delete('/locales/{id}', [LocaleController::class, 'destroy']);
    });

    Route::middleware(['permission:channel.view'])->group(function () {
        Route::get('/channels', [ChannelController::class, 'index']);
        Route::get('/channels/enabled', [ChannelController::class, 'enabled']);
        Route::get('/channels/{id}', [ChannelController::class, 'show']);
        Route::get('/channels/{channelCode}/locale', [ChannelController::class, 'getChannelLocale']);
        Route::get('/channels/{channelCode}/currency', [ChannelController::class, 'getChannelCurrency']);
    });

    Route::middleware(['permission:channel.create'])->group(function () {
        Route::post('/channels', [ChannelController::class, 'store']);
    });

    Route::middleware(['permission:channel.update'])->group(function () {
        Route::put('/channels/{id}', [ChannelController::class, 'update']);
        Route::put('/channels/{id}/locale', [ChannelController::class, 'updateLocale']);
    });

    Route::middleware(['permission:channel.delete'])->group(function () {
        Route::delete('/channels/{id}', [ChannelController::class, 'destroy']);
    });

    Route::middleware(['permission:currency.view'])->group(function () {
        Route::get('/currencies', [CurrencyController::class, 'index']);
        Route::get('/currencies/enabled', [CurrencyController::class, 'enabled']);
        Route::get('/currencies/{code}', [CurrencyController::class, 'show']);
    });

    Route::middleware(['permission:currency.create'])->group(function () {
        Route::post('/currencies', [CurrencyController::class, 'store']);
    });

    Route::middleware(['permission:currency.update'])->group(function () {
        Route::put('/currencies/{id}', [CurrencyController::class, 'update']);
    });

    Route::middleware(['permission:currency.delete'])->group(function () {
        Route::delete('/currencies/{id}', [CurrencyController::class, 'destroy']);
    });

    Route::middleware(['permission:exchange_rate.view'])->group(function () {
        Route::get('/exchange-rates', [ExchangeRateController::class, 'index']);
        Route::get('/exchange-rates/active', [ExchangeRateController::class, 'active']);
        Route::get('/exchange-rates/rate', [ExchangeRateController::class, 'getRate']);
        Route::post('/exchange-rates/matrix', [ExchangeRateController::class, 'matrix']);
    });

    Route::middleware(['permission:exchange_rate.convert'])->group(function () {
        Route::get('/exchange-rates/convert', [ExchangeRateController::class, 'convert']);
    });

    Route::middleware(['permission:exchange_rate.view'])->group(function () {
        Route::get('/exchange-rates/{id}', [ExchangeRateController::class, 'show']);
    });

    Route::middleware(['permission:exchange_rate.create'])->group(function () {
        Route::post('/exchange-rates', [ExchangeRateController::class, 'store']);
    });

    Route::middleware(['permission:exchange_rate.update'])->group(function () {
        Route::put('/exchange-rates/{id}', [ExchangeRateController::class, 'update']);
    });

    Route::middleware(['permission:exchange_rate.delete'])->group(function () {
        Route::delete('/exchange-rates/{id}', [ExchangeRateController::class, 'destroy']);
    });

    Route::middleware(['permission:exchange_rate.activate'])->group(function () {
        Route::post('/exchange-rates/{id}/activate', [ExchangeRateController::class, 'activate']);
    });

    Route::middleware(['permission:exchange_rate.deactivate'])->group(function () {
        Route::post('/exchange-rates/{id}/deactivate', [ExchangeRateController::class, 'deactivate']);
    });
});
