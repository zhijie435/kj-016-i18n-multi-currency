<?php

namespace Packages\AnnotationTask;

use Illuminate\Support\ServiceProvider;

class AnnotationTaskServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'annotation-task');

        $this->publishes([
            __DIR__ . '/Resources/lang' => resource_path('lang/vendor/annotation-task'),
        ], 'annotation-task-lang');
    }
}
