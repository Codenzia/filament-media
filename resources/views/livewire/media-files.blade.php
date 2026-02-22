<div>
    {{-- Layout Toggle --}}
    @if ($showLayoutToggle)
        <div class="flex items-center justify-end mb-4">
            <div class="inline-flex items-center rounded-lg bg-gray-100 dark:bg-gray-800 p-0.5">
                <button type="button" wire:click="setLayout('grid')"
                    class="p-1.5 rounded-md transition-colors {{ $layout === 'grid' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}"
                    title="{{ trans('filament-media::media.grid_view') }}">
                    <x-heroicon-m-squares-2x2 class="w-4 h-4" />
                </button>
                <button type="button" wire:click="setLayout('list')"
                    class="p-1.5 rounded-md transition-colors {{ $layout === 'list' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}"
                    title="{{ trans('filament-media::media.list_view') }}">
                    <x-heroicon-m-list-bullet class="w-4 h-4" />
                </button>
            </div>
        </div>
    @endif

    @if ($this->files->count() > 0)
        <div x-data="{
            contextMenu: { show: false, x: 0, y: 0, item: null },

            openContextMenu(event, itemData) {
                event.preventDefault();

                let x = event.clientX;
                let y = event.clientY;

                const menuWidth = 200;
                const menuHeight = 300;

                if (x + menuWidth > window.innerWidth) {
                    x = window.innerWidth - menuWidth - 8;
                }
                if (y + menuHeight > window.innerHeight) {
                    y = window.innerHeight - menuHeight - 8;
                }

                this.contextMenu = { show: true, x, y, item: itemData };
            },

            closeContextMenu() {
                this.contextMenu.show = false;
            },
        }">
            {{-- Grid or List content --}}
            @if ($layout === 'list')
                @include('filament-media::livewire.partials.media-file-list-content')
            @else
                @include('filament-media::livewire.partials.media-file-grid-content')
            @endif

            {{-- Context menu dropdown (single instance) --}}
            @include('filament-media::livewire.partials.media-file-context-menu')
        </div>
    @else
        <div class="text-center py-8">
            <div
                class="w-12 h-12 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                <x-filament::icon icon="heroicon-o-document" class="w-6 h-6 text-gray-400" />
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $emptyMessage ?: __('No files attached') }}</p>
        </div>
    @endif

    {{-- Filament Action Modals --}}
    <x-filament-actions::modals />
</div>
