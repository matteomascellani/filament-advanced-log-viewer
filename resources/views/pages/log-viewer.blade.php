<x-filament-panels::page>

@php
    $levelColors = [
        'emergency' => 'bg-red-700 text-white',
        'alert' => 'bg-red-600 text-white',
        'critical' => 'bg-red-500 text-white',
        'error' => 'bg-red-400 text-white',
        'warning' => 'bg-amber-400 text-white',
        'notice' => 'bg-blue-400 text-white',
        'info' => 'bg-blue-300 text-gray-800',
        'debug' => 'bg-gray-200 text-gray-600 dark:bg-white/10 dark:text-gray-300',
    ];
    $levelRowColors = [
        'emergency' => 'border-l-red-700',
        'alert' => 'border-l-red-600',
        'critical' => 'border-l-red-500',
        'error' => 'border-l-red-400',
        'warning' => 'border-l-amber-400',
        'notice' => 'border-l-blue-400',
        'info' => 'border-l-blue-300',
        'debug' => 'border-l-gray-300 dark:border-l-white/20',
    ];
    $channelMeta = config('filament-advanced-log-viewer.channel_meta', []);
    $fileGroups = $this->getFilteredFileGroups();
    $flatFiles = $this->getFlatSidebarFiles();
    $hasSidebarFilters = $this->hasSidebarFilters();
@endphp

<div
    class="flex gap-3"
    style="height: calc(100vh - var(--header-height, 4rem) - 2rem); min-height: 500px;"
>

    <div
        class="shrink-0 flex flex-col overflow-hidden rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900"
        style="flex: 0 0 14rem; width: 14rem; min-width: 14rem; max-width: 14rem;"
    >
        <div class="px-2 py-2 border-b border-gray-200 dark:border-white/10">
            <div>
                <input
                    wire:model.live.debounce.300ms="sidebarSearch"
                    type="search"
                    placeholder="Cerca log..."
                    class="w-full px-3 py-2 text-xs rounded-lg border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-primary-500"
                />
            </div>
            <div class="grid grid-cols-2 gap-2 mt-2">
                <label class="block">
                    <span class="block text-[10px] text-gray-500 dark:text-gray-400 mb-1">Da</span>
                    <input
                        wire:model.live="sidebarDateFrom"
                        type="date"
                        class="w-full px-2 py-1.5 text-[11px] rounded-lg border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-primary-500"
                    />
                </label>
                <label class="block">
                    <span class="block text-[10px] text-gray-500 dark:text-gray-400 mb-1">A</span>
                    <input
                        wire:model.live="sidebarDateTo"
                        type="date"
                        class="w-full px-2 py-1.5 text-[11px] rounded-lg border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-primary-500"
                    />
                </label>
            </div>
        </div>

        <div class="overflow-y-auto flex-1">
            @if ($hasSidebarFilters)
                @forelse ($flatFiles as $file)
                    <button
                        type="button"
                        wire:click="selectFile('{{ addslashes($file['path']) }}')"
                        @class([
                            'w-full text-left pr-3 py-2 border-l-2 transition',
                            'bg-primary-50 dark:bg-primary-900/20 border-l-primary-500 text-primary-700 dark:text-primary-300' => $file['path'] === $selectedFile,
                            'border-l-transparent text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5' => $file['path'] !== $selectedFile,
                        ])
                        style="padding-left: 1rem;"
                    >
                        <div class="text-sm font-semibold truncate">{{ Str::of($file['channel'])->replace('-', ' ')->title() }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-300 truncate">{{ $file['label'] }}</div>
                        <div class="text-[10px] text-gray-400 dark:text-gray-500">{{ $file['size'] }} KB</div>
                    </button>
                @empty
                    <div class="px-3 py-6 text-xs text-gray-400 text-center">Nessun file</div>
                @endforelse
            @else
                @forelse ($fileGroups as $channel => $files)
                    @php
                        $meta = $channelMeta[$channel] ?? ['icon' => 'heroicon-o-document', 'color' => 'text-gray-400'];
                        $hasSelected = collect($files)->contains($selectedFile);
                    @endphp

                    <div
                        x-data="{ open: {{ $hasSelected || count($files) === 1 ? 'true' : 'false' }} }"
                        class="border-b border-gray-100 dark:border-white/5 last:border-0"
                    >
                        <button
                            type="button"
                            @click="open = !open"
                            class="w-full flex items-center gap-2 px-3 py-2 hover:bg-gray-50 dark:hover:bg-white/5 transition"
                        >
                            <x-dynamic-component :component="$meta['icon']" class="w-3.5 h-3.5 shrink-0 {{ $meta['color'] }}"/>
                            <span class="flex-1 text-left text-[11px] font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide truncate">
                                {{ $channel }}
                            </span>
                            <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ count($files) }}</span>
                            <svg :class="open ? 'rotate-180' : ''" class="w-3 h-3 text-gray-400 transition-transform shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" x-collapse>
                            @foreach ($files as $file)
                                <button
                                    type="button"
                                    wire:click="selectFile('{{ addslashes($file) }}')"
                                    @class([
                                        'w-full text-left pr-3 py-1.5 border-l-2 transition',
                                        'bg-primary-50 dark:bg-primary-900/20 border-l-primary-500 text-primary-700 dark:text-primary-300' => $file === $selectedFile,
                                        'border-l-transparent text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5' => $file !== $selectedFile,
                                    ])
                                    style="padding-left: 1rem;"
                                >
                                    <div class="text-sm font-semibold truncate">{{ Str::of($channel)->replace('-', ' ')->title() }}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-300 truncate">{{ $this->fileLabel($file) }}</div>
                                    <div class="text-[10px] text-gray-400 dark:text-gray-500">{{ $this->fileSizeKb($file) }} KB</div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="px-3 py-6 text-xs text-gray-400 text-center">Nessun file</div>
                @endforelse
            @endif
        </div>
    </div>

    <div class="flex-1 min-w-0 overflow-hidden rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 flex flex-col">

        @if (! $selectedFile)
            <div class="flex-1 flex flex-col items-center justify-center gap-2 text-gray-400 dark:text-gray-500">
                <x-heroicon-o-document-text class="w-10 h-10 opacity-30"/>
                <span class="text-sm">Seleziona un file di log</span>
            </div>
        @else
            <div class="px-4 py-2 border-b border-gray-200 dark:border-white/10 flex items-center gap-3 flex-wrap">
                <div class="flex-1 min-w-48">
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="search"
                        placeholder="Filtra nel contenuto..."
                        class="w-full text-xs rounded-lg border border-gray-300 dark:border-white/10 bg-gray-50 dark:bg-white/5 text-gray-700 dark:text-gray-200 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-primary-500"
                    />
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <label class="font-medium">Righe:</label>
                    <select wire:model.live="lines" class="text-xs rounded border border-gray-300 dark:border-white/10 bg-gray-50 dark:bg-white/5 text-gray-700 dark:text-gray-200 px-2 py-1">
                        <option value="50">50</option>
                        <option value="200">200</option>
                        <option value="500">500</option>
                        <option value="1000">1000</option>
                    </select>
                </div>
                <div class="text-xs text-gray-400 dark:text-gray-500 font-mono">
                    {{ basename($selectedFile) }}
                    &middot; {{ round(filesize($selectedFile) / 1024, 1) }} KB
                </div>
            </div>

            <div class="flex-1 overflow-y-auto">
                @php $entries = $this->getLogEntries(); @endphp

                @if (empty($entries))
                    <div class="flex items-center justify-center h-32 text-sm text-gray-400 dark:text-gray-500">
                        Nessuna voce trovata
                    </div>
                @else
                    <table class="w-full text-xs">
                        <tbody>
                            @foreach ($entries as $entry)
                                @php
                                    $lvl = $entry['level'] ?? 'debug';
                                    $badge = $levelColors[$lvl] ?? $levelColors['debug'];
                                    $row = $levelRowColors[$lvl] ?? $levelRowColors['debug'];
                                    $expanded = $this->formatExpandedEntry($entry);
                                @endphp
                                <tr
                                    x-data="{ open: false }"
                                    @class([
                                        'border-b border-gray-100 dark:border-white/5 border-l-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 align-top',
                                        $row,
                                    ])
                                    @click="open = !open"
                                >
                                    <td class="px-3 py-2 whitespace-nowrap text-gray-400 dark:text-gray-500 w-32">
                                        {{ $entry['datetime'] ? \Carbon\Carbon::parse($entry['datetime'])->format('d/m H:i:s') : '—' }}
                                    </td>
                                    <td class="px-2 py-2 w-20">
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold uppercase {{ $badge }}">
                                            {{ strtoupper($lvl) }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-2 text-gray-700 dark:text-gray-300 max-w-0 w-full">
                                        <div x-show="!open" class="truncate">{{ Str::limit($entry['message'], 220) }}</div>
                                        <div x-show="open" class="pt-1" @click.stop>
                                            @if (($expanded['type'] ?? 'raw') === 'json')
                                                <pre class="whitespace-pre-wrap break-words font-mono leading-relaxed text-[11px] text-sky-700 dark:text-sky-300">{{ $expanded['content'] ?? $entry['raw'] }}</pre>
                                            @elseif (($expanded['type'] ?? 'raw') === 'stack')
                                                <div class="rounded-md border border-red-200 dark:border-red-400/30 bg-red-50/60 dark:bg-red-950/30 p-2">
                                                    <div class="text-[11px] font-semibold text-red-700 dark:text-red-300 mb-2">{{ $expanded['headline'] ?? $entry['message'] }}</div>
                                                    <pre class="whitespace-pre-wrap break-words font-mono leading-relaxed text-[11px] text-red-700/90 dark:text-red-200">{{ $expanded['trace'] ?? $entry['raw'] }}</pre>
                                                </div>
                                            @else
                                                <pre class="whitespace-pre-wrap break-words font-mono leading-relaxed text-[11px]">{{ $expanded['content'] ?? $entry['raw'] }}</pre>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endif

    </div>

</div>

</x-filament-panels::page>
