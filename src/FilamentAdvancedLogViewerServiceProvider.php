<?php

namespace H2bit\FilamentAdvancedLogViewer;

use Illuminate\Support\ServiceProvider;

class FilamentAdvancedLogViewerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/filament-advanced-log-viewer.php', 'filament-advanced-log-viewer');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'filament-advanced-log-viewer');

        $this->publishes([
            __DIR__ . '/../config/filament-advanced-log-viewer.php' => config_path('filament-advanced-log-viewer.php'),
        ], 'filament-advanced-log-viewer-config');
    }
}
