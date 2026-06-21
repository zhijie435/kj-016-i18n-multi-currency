<?php

namespace App\Http\Middleware;

use App\Services\LocaleService;
use App\Services\ChannelService;
use App\Services\CurrencyService;
use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    protected LocaleService $localeService;
    protected ChannelService $channelService;
    protected CurrencyService $currencyService;

    public function __construct(
        LocaleService $localeService,
        ChannelService $channelService,
        CurrencyService $currencyService
    ) {
        $this->localeService = $localeService;
        $this->channelService = $channelService;
        $this->currencyService = $currencyService;
    }

    public function handle($request, Closure $next)
    {
        $availableLocales = $this->localeService->getAvailableCodes();
        $locale = $this->resolveLocale($request, $availableLocales);

        if (!in_array($locale, $availableLocales, true)) {
            $locale = $this->localeService->getDefaultCode();
        }

        App::setLocale($locale);
        Session::put('locale', $locale);

        $this->resolveCurrency($request);

        return $next($request);
    }

    protected function resolveLocale($request, array $availableLocales): string
    {
        $channelCode = $request->header('X-Channel-Code') ?? $request->input('channel');
        if ($channelCode) {
            try {
                $channelLocale = $this->channelService->getChannelLocaleCode($channelCode);
                if ($channelLocale && in_array($channelLocale, $availableLocales, true)) {
                    return $channelLocale;
                }
            } catch (\Exception $e) {
                report($e);
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

        return $this->localeService->getDefaultCode();
    }

    protected function resolveCurrency($request): void
    {
        $channelCode = $request->header('X-Channel-Code') ?? $request->input('channel');
        if ($channelCode) {
            try {
                $currency = $this->channelService->getChannelCurrency($channelCode);
                if ($currency && !empty($currency['code'])) {
                    Config::set('app.current_currency', $currency);
                    Session::put('currency', $currency);
                    return;
                }
            } catch (\Exception $e) {
                report($e);
            }
        }

        $sessionCurrency = Session::get('currency');
        if ($sessionCurrency) {
            Config::set('app.current_currency', $sessionCurrency);
            return;
        }

        $currency = $this->currencyService->getDefaultInfo();
        Config::set('app.current_currency', $currency);
    }
}
