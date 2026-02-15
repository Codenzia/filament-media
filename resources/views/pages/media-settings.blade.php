<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-x-3">
            <x-filament::button type="submit">
                {{ trans('filament-media::media.settings.save') }}
            </x-filament::button>
        </div>
    </form>

    {{-- Orphan Scan Section --}}
    <x-filament::section
        :heading="trans('filament-media::media.settings.scan_title')"
        :description="trans('filament-media::media.settings.scan_description')"
        collapsible
        collapsed
    >
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <x-filament::button
                    wire:click="scanStorage"
                    wire:loading.attr="disabled"
                    wire:target="scanStorage"
                    icon="heroicon-m-magnifying-glass"
                    color="gray"
                >
                    <span wire:loading.remove wire:target="scanStorage">
                        {{ trans('filament-media::media.settings.scan_button') }}
                    </span>
                    <span wire:loading wire:target="scanStorage">
                        {{ trans('filament-media::media.settings.scan_scanning') }}
                    </span>
                </x-filament::button>
            </div>

            @if($scanComplete && !empty($orphanedFiles))
                <div class="rounded-lg border border-gray-200 dark:border-white/10 overflow-hidden">
                    <div class="bg-gray-50 dark:bg-white/5 px-4 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <x-filament::input.checkbox
                                wire:click="toggleAllOrphans"
                                :checked="count($selectedOrphans) === count($orphanedFiles) && count($orphanedFiles) > 0"
                            />
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ trans('filament-media::media.settings.scan_found', ['count' => count($orphanedFiles)]) }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            @if(!empty($selectedOrphans))
                                <x-filament::button
                                    wire:click="importOrphans"
                                    size="sm"
                                    color="success"
                                    icon="heroicon-m-arrow-down-tray"
                                >
                                    {{ trans('filament-media::media.settings.scan_import_selected', ['count' => count($selectedOrphans)]) }}
                                </x-filament::button>
                                <x-filament::button
                                    wire:click="deleteOrphans"
                                    wire:confirm="{{ trans('filament-media::media.settings.scan_delete_confirm') }}"
                                    size="sm"
                                    color="danger"
                                    icon="heroicon-m-trash"
                                >
                                    {{ trans('filament-media::media.settings.scan_delete_selected', ['count' => count($selectedOrphans)]) }}
                                </x-filament::button>
                            @else
                                <x-filament::button
                                    wire:click="importAllOrphans"
                                    size="sm"
                                    color="success"
                                    icon="heroicon-m-arrow-down-tray"
                                >
                                    {{ trans('filament-media::media.settings.scan_import_all') }}
                                </x-filament::button>
                                <x-filament::button
                                    wire:click="deleteAllOrphans"
                                    wire:confirm="{{ trans('filament-media::media.settings.scan_delete_confirm') }}"
                                    size="sm"
                                    color="danger"
                                    icon="heroicon-m-trash"
                                >
                                    {{ trans('filament-media::media.settings.scan_delete_all') }}
                                </x-filament::button>
                            @endif
                        </div>
                    </div>

                    <div class="divide-y divide-gray-200 dark:divide-white/10 max-h-96 overflow-y-auto">
                        @foreach($orphanedFiles as $file)
                            <div class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-white/5 transition"
                                 wire:key="orphan-{{ md5($file['path']) }}">
                                <x-filament::input.checkbox
                                    wire:click="toggleOrphanSelection('{{ $file['path'] }}')"
                                    :checked="in_array($file['path'], $selectedOrphans)"
                                />
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $file['name'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ $file['path'] }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400 shrink-0">
                                    <span>{{ $file['mime_type'] }}</span>
                                    <span>{{ $file['size'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @elseif($scanComplete && empty($orphanedFiles))
                <div class="rounded-lg border border-success-200 dark:border-success-800 bg-success-50 dark:bg-success-950 p-4">
                    <p class="text-sm text-success-700 dark:text-success-400">
                        {{ trans('filament-media::media.settings.scan_no_orphans') }}
                    </p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-panels::page>
