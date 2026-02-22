<div>
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
            @include('filament-media::livewire.partials.media-file-list-content')

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
