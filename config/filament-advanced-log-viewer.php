<?php

return [
    'navigation_group' => 'Sistema',
    'navigation_label' => 'Registri',
    'navigation_icon' => 'heroicon-o-document-text',
    'navigation_sort' => 97,

    'slug' => 'advanced-log-viewer',
    'title' => '',

    // Example: storage_path('logs/*.log')
    'logs_glob' => storage_path('logs/*.log'),

    // Access control is handled via the fluent plugin API.
    // In your PanelProvider:
    //
    //   FilamentAdvancedLogViewerPlugin::make()
    //       ->canAccess(fn () => auth()->user()?->hasRole('admin'))
    //
    // By default (no callback) anyone authenticated in the panel can access the page.

    'channel_meta' => [
        'laravel' => ['icon' => 'heroicon-o-cube', 'color' => 'text-blue-500'],
        'trace' => ['icon' => 'heroicon-o-magnifying-glass', 'color' => 'text-purple-500'],
        'stripe' => ['icon' => 'heroicon-o-credit-card', 'color' => 'text-indigo-500'],
        'stripe-legacy' => ['icon' => 'heroicon-o-credit-card', 'color' => 'text-indigo-300'],
        'paypal' => ['icon' => 'heroicon-o-banknotes', 'color' => 'text-sky-500'],
        'paypal-legacy' => ['icon' => 'heroicon-o-banknotes', 'color' => 'text-sky-300'],
        'fic' => ['icon' => 'heroicon-o-document-text', 'color' => 'text-teal-500'],
        'sheets' => ['icon' => 'heroicon-o-table-cells', 'color' => 'text-green-500'],
        'jobs' => ['icon' => 'heroicon-o-queue-list', 'color' => 'text-amber-500'],
        'google' => ['icon' => 'heroicon-o-globe-alt', 'color' => 'text-red-400'],
        'whatsapp' => ['icon' => 'heroicon-o-chat-bubble-left-ellipsis', 'color' => 'text-green-400'],
    ],
];
