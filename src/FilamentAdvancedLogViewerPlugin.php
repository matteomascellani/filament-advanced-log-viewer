<?php

namespace H2bit\FilamentAdvancedLogViewer;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use H2bit\FilamentAdvancedLogViewer\Pages\LogViewerPage;

class FilamentAdvancedLogViewerPlugin implements Plugin
{
    protected ?Closure $canAccessCallback = null;
    protected ?string $navigationGroup = null;
    protected ?string $navigationLabel = null;
    protected ?string $navigationIcon = null;
    protected ?int $navigationSort = null;

    public function getId(): string
    {
        return 'h2bit-filament-advanced-log-viewer';
    }

    /**
     * Customize who can access the log viewer.
     *
     * Usage in PanelProvider:
     *   FilamentAdvancedLogViewerPlugin::make()
     *       ->canAccess(fn () => auth()->user()->hasRole('admin'))
     */
    public function canAccess(Closure $callback): static
    {
        $this->canAccessCallback = $callback;

        return $this;
    }

    public function navigationGroup(string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function navigationLabel(string $label): static
    {
        $this->navigationLabel = $label;

        return $this;
    }

    public function navigationIcon(string $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function navigationSort(int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function register(Panel $panel): void
    {
        if ($this->canAccessCallback !== null) {
            app()->instance('filament-advanced-log-viewer.can-access', $this->canAccessCallback);
        }
        if ($this->navigationGroup !== null) {
            app()->instance('filament-advanced-log-viewer.navigation-group', $this->navigationGroup);
        }
        if ($this->navigationLabel !== null) {
            app()->instance('filament-advanced-log-viewer.navigation-label', $this->navigationLabel);
        }
        if ($this->navigationIcon !== null) {
            app()->instance('filament-advanced-log-viewer.navigation-icon', $this->navigationIcon);
        }
        if ($this->navigationSort !== null) {
            app()->instance('filament-advanced-log-viewer.navigation-sort', $this->navigationSort);
        }

        $panel->pages([
            LogViewerPage::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        // No-op
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
