<?php

return [

    'name' => env('APP_NAME', '内容审核标注平台'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    'timezone' => env('APP_TIMEZONE', 'Asia/Shanghai'),

    'locale' => env('APP_DEFAULT_LOCALE', 'zh_CN'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_DEFAULT_LOCALE', 'zh_CN'),

    'available_locales' => [
        'zh_CN' => ['name' => '简体中文', 'native' => '简体中文'],
        'en'    => ['name' => 'English',   'native' => 'English'],
        'pt_BR' => ['name' => 'Portuguese', 'native' => 'Português'],
        'ru'    => ['name' => 'Russian',   'native' => 'Русский'],
    ],

    'default_currency' => env('APP_DEFAULT_CURRENCY', 'CNY'),

    'default_role' => 'viewer',

    'available_currencies' => [
        'CNY' => ['name' => '人民币', 'symbol' => '¥', 'code' => 'CNY', 'decimals' => 2],
        'USD' => ['name' => '美元',   'symbol' => '$', 'code' => 'USD', 'decimals' => 2],
        'EUR' => ['name' => '欧元',   'symbol' => '€', 'code' => 'EUR', 'decimals' => 2],
        'BRL' => ['name' => '巴西雷亚尔', 'symbol' => 'R$', 'code' => 'BRL', 'decimals' => 2],
        'RUB' => ['name' => '俄罗斯卢布', 'symbol' => '₽', 'code' => 'RUB', 'decimals' => 2],
    ],

    'multi_language_enabled' => env('MULTI_LANGUAGE_ENABLED', true),

    'multi_currency_enabled' => env('MULTI_CURRENCY_ENABLED', true),

    'currency_conversion_precision' => env('CURRENCY_CONVERSION_PRECISION', 8),

    'currency_rounding_mode' => env('CURRENCY_ROUNDING_MODE', 'half_up'),

    'exchange_rate' => [
        'api_enabled' => env('EXCHANGE_RATE_API_ENABLED', false),
        'api_source' => env('EXCHANGE_RATE_API_SOURCE', 'manual'),
        'api_key' => env('EXCHANGE_RATE_API_KEY', ''),
        'api_url' => env('EXCHANGE_RATE_API_URL', ''),
        'auto_update' => env('EXCHANGE_RATE_AUTO_UPDATE', false),
        'update_interval' => env('EXCHANGE_RATE_UPDATE_INTERVAL', 3600),
        'cache_ttl' => env('EXCHANGE_RATE_CACHE_TTL', 1800),
    ],

    'cache' => [
        'locale_ttl' => env('LOCALE_CACHE_TTL', 3600),
        'currency_ttl' => env('CURRENCY_CACHE_TTL', 3600),
    ],

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    'providers' => [
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        App\Providers\RouteServiceProvider::class,
        App\Providers\PermissionServiceProvider::class,

        Packages\AnnotationTask\AnnotationTaskServiceProvider::class,
        Packages\ContentReview\ContentReviewServiceProvider::class,
    ],

    'aliases' => [
        'App'          => Illuminate\Support\Facades\App::class,
        'Arr'          => Illuminate\Support\Arr::class,
        'Artisan'      => Illuminate\Support\Facades\Artisan::class,
        'Auth'         => Illuminate\Support\Facades\Auth::class,
        'Blade'        => Illuminate\Support\Facades\Blade::class,
        'Broadcast'    => Illuminate\Support\Facades\Broadcast::class,
        'Bus'          => Illuminate\Support\Facades\Bus::class,
        'Cache'        => Illuminate\Support\Facades\Cache::class,
        'Config'       => Illuminate\Support\Facades\Config::class,
        'Cookie'       => Illuminate\Support\Facades\Cookie::class,
        'Crypt'        => Illuminate\Support\Facades\Crypt::class,
        'DB'           => Illuminate\Support\Facades\DB::class,
        'Eloquent'     => Illuminate\Database\Eloquent\Model::class,
        'Event'        => Illuminate\Support\Facades\Event::class,
        'File'         => Illuminate\Support\Facades\File::class,
        'Gate'         => Illuminate\Support\Facades\Gate::class,
        'Hash'         => Illuminate\Support\Facades\Hash::class,
        'Http'         => Illuminate\Support\Facades\Http::class,
        'Lang'         => Illuminate\Support\Facades\Lang::class,
        'Log'          => Illuminate\Support\Facades\Log::class,
        'Mail'         => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password'     => Illuminate\Support\Facades\Password::class,
        'Queue'        => Illuminate\Support\Facades\Queue::class,
        'Redirect'     => Illuminate\Support\Facades\Redirect::class,
        'Redis'        => Illuminate\Support\Facades\Redis::class,
        'Request'      => Illuminate\Support\Facades\Request::class,
        'Response'     => Illuminate\Support\Facades\Response::class,
        'Route'        => Illuminate\Support\Facades\Route::class,
        'Schema'       => Illuminate\Support\Facades\Schema::class,
        'Session'      => Illuminate\Support\Facades\Session::class,
        'Storage'      => Illuminate\Support\Facades\Storage::class,
        'Str'          => Illuminate\Support\Str::class,
        'URL'          => Illuminate\Support\Facades\URL::class,
        'Validator'    => Illuminate\Support\Facades\Validator::class,
        'View'         => Illuminate\Support\Facades\View::class,
    ],

];
