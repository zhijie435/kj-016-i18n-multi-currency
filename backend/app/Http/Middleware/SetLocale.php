<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    public function handle($request, Closure $next)
    {
        $availableLocales = array_keys(Config::get('app.available_locales', []));

        $locale = $request->header('X-App-Locale')
            ?? $request->input('locale')
            ?? Session::get('locale')
            ?? $request->getPreferredLanguage($availableLocales)
            ?? Config::get('app.locale');

        if (!in_array($locale, $availableLocales, true)) {
            $locale = Config::get('app.fallback_locale', 'en');
        }

        App::setLocale($locale);
        Session::put('locale', $locale);

        return $next($request);
    }
}
