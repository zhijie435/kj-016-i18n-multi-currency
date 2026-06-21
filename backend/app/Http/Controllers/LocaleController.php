<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;

class LocaleController extends Controller
{
    public function index()
    {
        $availableLocales = Config::get('app.available_locales', []);
        $currentLocale = App::getLocale();

        return response()->json([
            'current' => $currentLocale,
            'available' => $availableLocales,
        ]);
    }

    public function show($locale)
    {
        $availableLocales = array_keys(Config::get('app.available_locales', []));

        if (!in_array($locale, $availableLocales, true)) {
            return response()->json([
                'error' => 'Unsupported locale',
                'available' => $availableLocales,
            ], 400);
        }

        App::setLocale($locale);

        $messages = [
            'auth'      => Lang::get('auth'),
            'pagination' => Lang::get('pagination'),
            'passwords' => Lang::get('passwords'),
            'validation' => Lang::get('validation'),
            'common'    => Lang::get('common'),
            'menu'      => Lang::get('menu'),
        ];

        return response()->json([
            'locale'   => $locale,
            'messages' => $messages,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'locale' => 'required|string',
        ]);

        $availableLocales = array_keys(Config::get('app.available_locales', []));
        $locale = $validated['locale'];

        if (!in_array($locale, $availableLocales, true)) {
            return response()->json([
                'error' => 'Unsupported locale',
                'available' => $availableLocales,
            ], 400);
        }

        App::setLocale($locale);
        session()->put('locale', $locale);

        return response()->json([
            'success' => true,
            'locale'  => $locale,
            'message' => 'Locale updated successfully',
        ]);
    }
}
