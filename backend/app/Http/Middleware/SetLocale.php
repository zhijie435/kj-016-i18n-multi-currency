<?php

namespace App\Http\Middleware;

use App\Models\Channel;
use App\Models\Locale;
use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    public function handle($request, Closure $next)
    {
        try {
            $availableLocales = Locale::getAvailableLocaleCodes();
        } catch (\Exception $e) {
            $availableLocales = array_keys(Config::get('app.available_locales', []));
        }

        $locale = $this->resolveLocale($request, $availableLocales);

        if (!in_array($locale, $availableLocales, true)) {
            try {
                $locale = Locale::getDefaultLocale();
            } catch (\Exception $e) {
                $locale = Config::get('app.fallback_locale', 'en');
            }
        }

        App::setLocale($locale);
        Session::put('locale', $locale);

        return $next($request);
    }

    protected function resolveLocale($request, array $availableLocales): string
    {
        $channelCode = $request->header('X-Channel-Code') ?? $request->input('channel');
        if ($channelCode) {
            try {
                $channelLocale = Channel::getChannelLocaleCode($channelCode);
                if ($channelLocale && in_array($channelLocale, $availableLocales, true)) {
                    return $channelLocale;
                }
            } catch (\Exception $e) {
            }
        }

        $headerLocale = $request->header('X-App-Locale');
        if ($headerLocale && in_array($headerLocale, $availableLocales, true)) {
            return $headerLocale;
        }

        $inputLocale = $request->input('locale');
        if ($inputLocale && in_array($inputLocale, $availableLocales, true)) {
            return $inputLocale;
        }

        $sessionLocale = Session::get('locale');
        if ($sessionLocale && in_array($sessionLocale, $availableLocales, true)) {
            return $sessionLocale;
        }

        $preferredLocale = $request->getPreferredLanguage($availableLocales);
        if ($preferredLocale && in_array($preferredLocale, $availableLocales, true)) {
            return $preferredLocale;
        }

        try {
            return Locale::getDefaultLocale();
        } catch (\Exception $e) {
            return Config::get('app.locale', 'zh_CN');
        }
    }
}
