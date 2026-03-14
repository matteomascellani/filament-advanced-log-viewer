<?php

namespace H2bit\FilamentAdvancedLogViewer\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class LogViewerPage extends Page
{
    protected static string $view = 'filament-advanced-log-viewer::pages.log-viewer';

    protected ?string $maxContentWidth = 'full';

    public ?string $selectedFile = null;

    public string $search = '';

    public string $sidebarSearch = '';

    public ?string $sidebarDateFrom = null;

    public ?string $sidebarDateTo = null;

    public int $lines = 200;

    public static function getNavigationGroup(): ?string
    {
        return app()->bound('filament-advanced-log-viewer.navigation-group')
            ? app('filament-advanced-log-viewer.navigation-group')
            : config('filament-advanced-log-viewer.navigation_group', 'Sistema');
    }

    public static function getNavigationLabel(): string
    {
        return app()->bound('filament-advanced-log-viewer.navigation-label')
            ? (string) app('filament-advanced-log-viewer.navigation-label')
            : (string) config('filament-advanced-log-viewer.navigation_label', 'Registri');
    }

    public static function getNavigationIcon(): string
    {
        return app()->bound('filament-advanced-log-viewer.navigation-icon')
            ? (string) app('filament-advanced-log-viewer.navigation-icon')
            : (string) config('filament-advanced-log-viewer.navigation_icon', 'heroicon-m-document-text');
    }

    public static function getNavigationSort(): ?int
    {
        return app()->bound('filament-advanced-log-viewer.navigation-sort')
            ? (int) app('filament-advanced-log-viewer.navigation-sort')
            : (int) config('filament-advanced-log-viewer.navigation_sort', 97);
    }

    public static function getSlug(): string
    {
        return (string) config('filament-advanced-log-viewer.slug', 'advanced-log-viewer');
    }

    public function getTitle(): string
    {
        return (string) config('filament-advanced-log-viewer.title', '');
    }

    /** @return array<string, array<string>> grouped by channel name, files sorted newest first */
    public function getFileGroups(): array
    {
        $pattern = (string) config('filament-advanced-log-viewer.logs_glob', storage_path('logs/*.log'));
        $files = File::glob($pattern) ?: [];

        $groups = [];
        foreach ($files as $file) {
            $basename = basename($file);
            $channel = preg_replace('/-\d{4}-\d{2}-\d{2}$/', '', pathinfo($basename, PATHINFO_FILENAME));
            $groups[$channel][] = $file;
        }

        // Sort each channel's files newest first by date in filename, fallback to mtime.
        foreach ($groups as &$groupFiles) {
            usort($groupFiles, function ($a, $b) {
                preg_match('/(\d{4}-\d{2}-\d{2})/', basename($a), $ma);
                preg_match('/(\d{4}-\d{2}-\d{2})/', basename($b), $mb);
                if (isset($ma[1], $mb[1])) {
                    return strcmp($mb[1], $ma[1]);
                }

                return filemtime($b) <=> filemtime($a);
            });
        }
        unset($groupFiles);

        // Keep channel groups alphabetical for predictable navigation.
        uksort($groups, fn ($a, $b) => strcasecmp((string) $a, (string) $b));

        return $groups;
    }

    public function getLogEntries(): array
    {
        if (! $this->selectedFile || ! File::exists($this->selectedFile)) {
            return [];
        }

        $selectedChannel = $this->detectChannelFromFile($this->selectedFile);

        // Read last N lines efficiently.
        $content = $this->tailFile($this->selectedFile, max(500, $this->lines * 3));

        // Split into entries where each starts with [YYYY-MM-DD HH:MM:SS].
        $rawEntries = preg_split('/(?=\[\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2})/m', $content, -1, PREG_SPLIT_NO_EMPTY);
        $rawEntries = array_reverse(array_values(array_filter(array_map('trim', $rawEntries))));

        $entries = [];
        foreach ($rawEntries as $raw) {
            if ($this->search !== '' && ! str_contains(strtolower($raw), strtolower($this->search))) {
                continue;
            }

            $parsed = $this->parseEntry($raw);
            if (! $parsed['channel'] && $selectedChannel !== '') {
                $parsed['channel'] = $selectedChannel;
            }

            $entries[] = $parsed;
            if (count($entries) >= $this->lines) {
                break;
            }
        }

        return $entries;
    }

    /** @return array<string, array<string>> */
    public function getFilteredFileGroups(): array
    {
        $groups = $this->getFileGroups();

        foreach ($groups as $channel => $files) {
            $groups[$channel] = array_values(array_filter($files, fn ($file) => $this->matchesSidebarFilters($file, $channel)));
            if (empty($groups[$channel])) {
                unset($groups[$channel]);
            }
        }

        return $groups;
    }

    /** @return array<int, array<string, mixed>> */
    public function getFlatSidebarFiles(): array
    {
        $flat = [];

        foreach ($this->getFilteredFileGroups() as $channel => $files) {
            foreach ($files as $file) {
                $date = $this->fileDateForFilter($file) ?? date('Y-m-d', filemtime($file));

                $flat[] = [
                    'path' => $file,
                    'label' => $this->fileLabel($file),
                    'channel' => $channel,
                    'size' => $this->fileSizeKb($file),
                    'date_sort' => $date,
                ];
            }
        }

        usort($flat, function (array $a, array $b) {
            $channelCmp = strcasecmp((string) $a['channel'], (string) $b['channel']);
            if ($channelCmp !== 0) {
                return $channelCmp;
            }

            if ($a['date_sort'] !== $b['date_sort']) {
                return strcmp((string) $b['date_sort'], (string) $a['date_sort']);
            }

            return strcmp((string) $a['path'], (string) $b['path']);
        });

        return $flat;
    }

    public function hasSidebarFilters(): bool
    {
        return trim($this->sidebarSearch) !== ''
            || ! empty($this->sidebarDateFrom)
            || ! empty($this->sidebarDateTo);
    }

    private function parseEntry(string $raw): array
    {
        $level = 'debug';
        $datetime = null;
        $channel = null;
        $message = $raw;

        if (preg_match('/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[+-]\d{2}:\d{2})?)\]\s+([a-z0-9_\-]+)\.([A-Z]+):\s+(.+)/s', $raw, $m)) {
            $datetime = $m[1];
            $channel = $m[2];
            $level = strtolower($m[3]);
            $message = trim($m[4]);
        } elseif (preg_match('/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}[^\]]*)\]\s+(.+)/s', $raw, $m)) {
            $datetime = $m[1];
            $message = trim($m[2]);
        }

        return [
            'datetime' => $datetime,
            'channel' => $channel,
            'level' => $level,
            'message' => $message,
            'raw' => $raw,
        ];
    }

    private function tailFile(string $path, int $maxLines): string
    {
        $fp = fopen($path, 'rb');
        if (! $fp) {
            return '';
        }

        fseek($fp, 0, SEEK_END);
        $size = ftell($fp);
        $chunkSize = 65536;
        $buffer = '';
        $newlines = 0;

        while ($size > 0 && $newlines < $maxLines) {
            $readSize = min($chunkSize, $size);
            $size -= $readSize;
            fseek($fp, $size);
            $buffer = fread($fp, $readSize) . $buffer;
            $newlines = substr_count($buffer, "\n");
        }

        fclose($fp);

        return $buffer;
    }

    public function fileLabel(string $file): string
    {
        $date = $this->fileDateForFilter($file);
        if (! $date) {
            return basename($file);
        }

        return Carbon::parse($date)->format('d/m/Y');
    }

    public function fileSizeKb(string $file): float
    {
        return round(filesize($file) / 1024, 1);
    }

    public function formatExpandedEntry(array $entry): array
    {
        $raw = (string) ($entry['raw'] ?? '');
        $message = (string) ($entry['message'] ?? '');
        $channel = strtolower((string) ($entry['channel'] ?? ''));

        if (in_array($channel, ['jobs', 'fic'], true)) {
            $pretty = $this->tryPrettifyJson($message);
            if ($pretty !== null) {
                return [
                    'type' => 'json',
                    'content' => $pretty,
                ];
            }
        }

        if ($channel === 'laravel') {
            return [
                'type' => 'stack',
                'headline' => $message,
                'trace' => $this->extractStackTrace($raw),
            ];
        }

        return [
            'type' => 'raw',
            'content' => $raw,
        ];
    }

    private function detectChannelFromFile(string $file): string
    {
        $basename = basename($file);
        $channel = preg_replace('/-\d{4}-\d{2}-\d{2}\.log$/', '', $basename);

        return is_string($channel) ? strtolower(trim($channel)) : '';
    }

    private function matchesSidebarFilters(string $file, string $channel): bool
    {
        $needle = strtolower(trim($this->sidebarSearch));
        if ($needle !== '') {
            $haystack = strtolower($channel . ' ' . basename($file) . ' ' . $this->fileLabel($file));
            if (! str_contains($haystack, $needle)) {
                return false;
            }
        }

        $fileDate = $this->fileDateForFilter($file) ?? date('Y-m-d', filemtime($file));

        if (! empty($this->sidebarDateFrom) && $fileDate < $this->sidebarDateFrom) {
            return false;
        }

        if (! empty($this->sidebarDateTo) && $fileDate > $this->sidebarDateTo) {
            return false;
        }

        return true;
    }

    private function fileDateForFilter(string $file): ?string
    {
        if (preg_match('/(\d{4}-\d{2}-\d{2})/', basename($file), $m) !== 1) {
            return null;
        }

        return $m[1];
    }

    private function extractStackTrace(string $raw): string
    {
        $parts = preg_split('/\{\"exception\"\:/', $raw, 2);
        if (! isset($parts[1])) {
            return $raw;
        }

        $jsonChunk = '{"exception":' . $parts[1];
        $decoded = json_decode($jsonChunk, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return $raw;
        }

        $exception = $decoded['exception'] ?? null;
        if (! is_array($exception)) {
            return $raw;
        }

        $trace = (string) ($exception['trace'] ?? '');
        if ($trace === '') {
            return $raw;
        }

        return trim($trace);
    }

    private function tryPrettifyJson(string $text): ?string
    {
        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (preg_match('/(\{.*\}|\[.*\])/s', $text, $m) !== 1) {
            return null;
        }

        $decoded = json_decode($m[1], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function updatedSearch(): void
    {
        // Livewire handles re-render.
    }

    public function updatedLines(): void
    {
        // Livewire handles re-render.
    }

    public function selectFile(string $file): void
    {
        $this->selectedFile = $file;
        $this->search = '';
    }

    public static function canAccess(): bool
    {
        // Custom closure via FilamentAdvancedLogViewerPlugin::make()->canAccess(fn () => ...)
        if (app()->bound('filament-advanced-log-viewer.can-access')) {
            return (bool) app('filament-advanced-log-viewer.can-access')();
        }

        // Default: anyone already authenticated in the panel.
        return auth()->check();
    }
}
