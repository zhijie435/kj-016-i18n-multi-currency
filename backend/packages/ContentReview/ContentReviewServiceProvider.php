<?php

namespace Packages\ContentReview;

use Illuminate\Support\ServiceProvider;

class ContentReviewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'content-review');

        $this->publishes([
            __DIR__ . '/Resources/lang' => resource_path('lang/vendor/content-review'),
        ], 'content-review-lang');
    }
}
