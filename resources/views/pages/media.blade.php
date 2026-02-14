<x-filament-panels::page x-data="{
    ctrlKey: false,
    shiftKey: false,
    lastSelectedIndex: null,
    contextMenu: { show: false, x: 0, y: 0, item: null },
    focusedIndex: -1,
    viewIn: '{{ $viewIn }}',

    handleKeydown(e) {
        // Don't handle shortcuts if typing in an input
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) {
            return;
        }

        // Get current items count from the wire component
        const itemCount = {{ $this->items->count() }};
        const selectedCount = {{ count($selectedItems) }};
        const columns = this.getGridColumns();

        switch (e.key) {
            // Arrow navigation
            case 'ArrowRight':
                e.preventDefault();
                this.navigate(1, itemCount);
                break;
            case 'ArrowLeft':
                e.preventDefault();
                this.navigate(-1, itemCount);
                break;
            case 'ArrowDown':
                e.preventDefault();
                this.navigate(columns, itemCount);
                break;
            case 'ArrowUp':
                e.preventDefault();
                this.navigate(-columns, itemCount);
                break;

                // Enter: Open folder or preview file
            case 'Enter':
                if (this.focusedIndex >= 0) {
                    e.preventDefault();
                    $wire.openItemByIndex(this.focusedIndex);
                }
                break;

                // Space: Toggle selection
            case ' ':
                if (this.focusedIndex >= 0) {
                    e.preventDefault();
                    $wire.toggleSelectionByIndex(this.focusedIndex);
                }
                break;

                // Delete: Move to trash
            case 'Delete':
            case 'Backspace':
                if (selectedCount > 0) {
                    e.preventDefault();
                    $wire.$dispatch('open-trash-modal', { items: $wire.selectedItems });
                }
                break;

                // F2: Rename
            case 'F2':
                if (selectedCount === 1) {
                    e.preventDefault();
                    $wire.$dispatch('open-rename-modal', { items: $wire.selectedItems });
                }
                break;

                // Ctrl/Cmd + A: Select all
            case 'a':
            case 'A':
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();
                    $wire.selectAll();
                }
                break;

                // Escape: Clear selection
            case 'Escape':
                this.contextMenu.show = false;
                $wire.clearSelection();
                this.focusedIndex = -1;
                break;

                // Home: Go to first item
            case 'Home':
                if (itemCount > 0) {
                    e.preventDefault();
                    this.focusedIndex = 0;
                    this.selectFocused();
                }
                break;

                // End: Go to last item
            case 'End':
                if (itemCount > 0) {
                    e.preventDefault();
                    this.focusedIndex = itemCount - 1;
                    this.selectFocused();
                }
                break;
        }
    },

    navigate(delta, itemCount) {
        if (itemCount === 0) return;

        if (this.focusedIndex < 0) {
            this.focusedIndex = 0;
        } else {
            this.focusedIndex = Math.max(0, Math.min(itemCount - 1, this.focusedIndex + delta));
        }

        this.selectFocused();
        this.scrollToFocused();
    },

    selectFocused() {
        if (this.focusedIndex >= 0) {
            $wire.selectByIndex(this.focusedIndex, this.ctrlKey);
            this.lastSelectedIndex = this.focusedIndex;
        }
    },

    scrollToFocused() {
        this.$nextTick(() => {
            const focusedEl = document.querySelector(`[data-item-index='${this.focusedIndex}']`);
            if (focusedEl) {
                focusedEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        });
    },

    getGridColumns() {
        // Approximate column count based on screen width
        const width = window.innerWidth;
        if (width >= 1536) return 8; // 2xl
        if (width >= 1280) return 6; // xl
        if (width >= 1024) return 5; // lg
        if (width >= 768) return 4; // md
        if (width >= 640) return 3; // sm
        return 2;
    }
}"
    x-on:keydown.window="ctrlKey = $event.ctrlKey || $event.metaKey; shiftKey = $event.shiftKey; handleKeydown($event)"
    x-on:keyup.window="ctrlKey = $event.ctrlKey || $event.metaKey; shiftKey = $event.shiftKey"
    x-on:click.away="contextMenu.show = false">
    {{-- Main Container --}}
    <div
        class="fm-container flex flex-col h-[calc(100vh-12rem)] bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">

        {{-- Top Toolbar - Reorganized Layout --}}
        <header class="fm-toolbar flex-shrink-0 flex flex-wrap items-center justify-between gap-3 p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
            {{-- LEFT side: Search, View In, Filter, Refresh --}}
            <div class="flex items-center gap-3">
                {{-- Search --}}
                <div class="relative">
                    <x-filament::input.wrapper>
                        <x-filament::input type="search" wire:model.live.debounce.300ms="search"
                            placeholder="{{ trans('filament-media::media.search_in_current_folder') }}"
                            class="w-40 sm:w-48" />
                    </x-filament::input.wrapper>
                </div>

                {{-- View In Dropdown (All media) --}}
                @php
                    $viewIcon = match ($viewIn) {
                        'trash' => 'heroicon-m-trash',
                        'recent' => 'heroicon-m-clock',
                        'favorites' => 'heroicon-m-star',
                        default => 'heroicon-m-photo',
                    };
                    $viewLabel = match ($viewIn) {
                        'trash' => trans('filament-media::media.trash'),
                        'recent' => trans('filament-media::media.recent'),
                        'favorites' => trans('filament-media::media.favorites'),
                        default => trans('filament-media::media.all_media'),
                    };
                    $viewBadgeColor = match ($viewIn) {
                        'trash' => 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300',
                        'recent' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300',
                        'favorites' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
                        default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                    };
                @endphp
                <x-filament-media::toolbar-dropdown :icon="$viewIcon" :label="$viewLabel" :badge-color="$viewBadgeColor">
                    <x-filament::dropdown.list.item icon="heroicon-m-photo" wire:click="setViewIn('all_media')"
                        :color="$viewIn === 'all_media' ? 'primary' : 'gray'">
                        {{ trans('filament-media::media.all_media') }}
                    </x-filament::dropdown.list.item>

                    <x-filament::dropdown.list.item icon="heroicon-m-clock" wire:click="setViewIn('recent')"
                        :color="$viewIn === 'recent' ? 'primary' : 'gray'">
                        {{ trans('filament-media::media.recent') }}
                    </x-filament::dropdown.list.item>

                    <x-filament::dropdown.list.item icon="heroicon-m-star" wire:click="setViewIn('favorites')"
                        :color="$viewIn === 'favorites' ? 'primary' : 'gray'">
                        {{ trans('filament-media::media.favorites') }}
                    </x-filament::dropdown.list.item>

                    <x-filament::dropdown.list.item icon="heroicon-m-trash" wire:click="setViewIn('trash')"
                        :color="$viewIn === 'trash' ? 'primary' : 'gray'">
                        {{ trans('filament-media::media.trash') }}
                    </x-filament::dropdown.list.item>
                </x-filament-media::toolbar-dropdown>

                {{-- Filter Dropdown (Everything) --}}
                @php
                    $filterIcon = match ($filter) {
                        'image' => 'heroicon-m-photo',
                        'video' => 'heroicon-m-film',
                        'document' => 'heroicon-m-document-text',
                        default => 'heroicon-m-squares-2x2',
                    };
                    $filterLabel = match ($filter) {
                        'image' => trans('filament-media::media.image'),
                        'video' => trans('filament-media::media.video'),
                        'document' => trans('filament-media::media.document'),
                        default => trans('filament-media::media.everything'),
                    };
                    $filterBadgeColor = $filter !== 'everything'
                        ? 'bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-300'
                        : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
                @endphp
                <x-filament-media::toolbar-dropdown :icon="$filterIcon" :label="$filterLabel" :badge-color="$filterBadgeColor">
                    <x-filament::dropdown.list.item icon="heroicon-m-squares-2x2"
                        wire:click="setFilter('everything')" :color="$filter === 'everything' ? 'primary' : 'gray'">
                        {{ trans('filament-media::media.everything') }}
                    </x-filament::dropdown.list.item>

                    @if (array_key_exists('image', $mimeTypes))
                        <x-filament::dropdown.list.item icon="heroicon-m-photo" wire:click="setFilter('image')"
                            :color="$filter === 'image' ? 'primary' : 'gray'">
                            {{ trans('filament-media::media.image') }}
                        </x-filament::dropdown.list.item>
                    @endif

                    @if (array_key_exists('video', $mimeTypes))
                        <x-filament::dropdown.list.item icon="heroicon-m-film" wire:click="setFilter('video')"
                            :color="$filter === 'video' ? 'primary' : 'gray'">
                            {{ trans('filament-media::media.video') }}
                        </x-filament::dropdown.list.item>
                    @endif

                    <x-filament::dropdown.list.item icon="heroicon-m-document-text"
                        wire:click="setFilter('document')" :color="$filter === 'document' ? 'primary' : 'gray'">
                        {{ trans('filament-media::media.document') }}
                    </x-filament::dropdown.list.item>
                </x-filament-media::toolbar-dropdown>

                {{-- Refresh --}}
                <x-filament::icon-button icon="heroicon-m-arrow-path" color="gray" size="sm" wire:click="refresh"
                    wire:loading.attr="disabled" wire:loading.class="animate-spin" :tooltip="trans('filament-media::media.refresh')" />

                {{-- Empty Trash (only in trash view) --}}
                @if ($viewIn === 'trash')
                    <x-filament::button color="danger" icon="heroicon-m-trash" size="sm"
                        wire:click="mountAction('empty_trash')">
                        {{ trans('filament-media::media.empty_trash') }}
                    </x-filament::button>
                @endif
            </div>

            {{-- RIGHT side: Sort, View Toggle --}}
            <div class="flex items-center gap-3">
                {{-- Sort Dropdown --}}
                @php
                    $sortIcon = str_ends_with($sortBy, '-desc') ? 'heroicon-m-arrow-down' : 'heroicon-m-arrow-up';
                    $sortLabel = $sorts[$sortBy]['label'] ?? trans('filament-media::media.uploaded_date_desc');
                @endphp
                <x-filament-media::toolbar-dropdown :icon="$sortIcon" :label="$sortLabel" placement="bottom-end">
                    @foreach ($sorts as $key => $sort)
                        <x-filament::dropdown.list.item :icon="$sort['icon']"
                            wire:click="setSortBy('{{ $key }}')" :color="$sortBy === $key ? 'primary' : 'gray'">
                            {{ $sort['label'] }}
                        </x-filament::dropdown.list.item>
                    @endforeach
                </x-filament-media::toolbar-dropdown>

                {{-- View Type Toggle --}}
                <div class="fm-view-toggle flex items-center gap-0.5">
                    <button type="button" wire:click="setViewType('grid')"
                        class="p-2 rounded-md transition-all duration-200
                            {{ $viewType === 'grid'
                                ? 'active text-primary-600 dark:text-primary-400'
                                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}"
                        title="{{ trans('filament-media::media.grid_view') }}">
                        <x-filament::icon icon="heroicon-m-squares-2x2" class="w-4 h-4" />
                    </button>
                    <button type="button" wire:click="setViewType('list')"
                        class="p-2 rounded-md transition-all duration-200
                            {{ $viewType === 'list'
                                ? 'active text-primary-600 dark:text-primary-400'
                                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}"
                        title="{{ trans('filament-media::media.list_view') }}">
                        <x-filament::icon icon="heroicon-m-list-bullet" class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </header>

        {{-- Breadcrumbs --}}
        <nav class="fm-breadcrumbs flex-shrink-0 flex items-center gap-2 px-4 py-2 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm overflow-x-auto">
            @foreach ($this->breadcrumbs as $index => $crumb)
                @if ($index > 0)
                    <x-filament::icon icon="heroicon-m-chevron-right" class="w-4 h-4 text-gray-400 flex-shrink-0" />
                @endif

                <button type="button" wire:click="navigateToFolder({{ $crumb['id'] }})"
                    class="flex items-center gap-1.5 text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors whitespace-nowrap {{ $loop->last ? 'font-medium text-gray-900 dark:text-white' : '' }}">
                    @if (isset($crumb['icon']))
                        {!! $crumb['icon'] !!}
                    @endif
                    <span>{{ $crumb['name'] }}</span>
                </button>
            @endforeach
        </nav>

        {{-- Main Content Area --}}
        <div class="fm-content flex flex-1 overflow-hidden">
            {{-- File Browser --}}
            <main x-data="{ isDropZone: false }" class="fm-browser flex-1 overflow-y-auto p-4 relative"
                :class="{ 'fm-drag-over': isDropZone }" wire:loading.class="opacity-50"
                x-on:contextmenu.prevent="if (!$event.target.closest('.fm-item')) { $wire.clearSelection() }"
                x-on:dragover.prevent="
                    if ($event.dataTransfer.types.includes('Files')) {
                        isDropZone = true;
                        $event.dataTransfer.dropEffect = 'copy';
                    }
                "
                x-on:dragleave.prevent="isDropZone = false"
                x-on:drop.prevent="
                    isDropZone = false;
                    if ($event.dataTransfer.files.length > 0) {
                        $dispatch('open-upload-modal', { folderId: {{ $folderId }} });
                    }
                ">
                {{-- Drop Overlay --}}
                <div x-show="isDropZone" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute inset-0 z-20 bg-primary-50/90 dark:bg-primary-900/50 border-2 border-dashed border-primary-500 rounded-lg flex items-center justify-center pointer-events-none">
                    <div class="text-center">
                        <x-filament::icon icon="heroicon-o-cloud-arrow-up"
                            class="w-12 h-12 text-primary-500 mx-auto mb-2" />
                        <p class="text-lg font-medium text-primary-600 dark:text-primary-400">
                            {{ trans('filament-media::media.drop_files_to_upload') }}
                        </p>
                    </div>
                </div>
                @if ($this->items->isEmpty())
                    {{-- Enhanced Empty State with Animations --}}
                    <div class="fm-empty-state flex flex-col items-center justify-center h-full text-center py-16">
                        {{-- Animated Icon --}}
                        <div class="fm-empty-icon relative mb-6">
                            <div
                                class="w-20 h-20 rounded-full bg-gradient-to-br from-primary-100 to-primary-200
                                dark:from-primary-900/40 dark:to-primary-800/40
                                flex items-center justify-center shadow-lg shadow-primary-500/20">
                                @if ($viewIn === 'trash')
                                    <x-filament::icon icon="heroicon-o-trash"
                                        class="w-10 h-10 text-gray-400 dark:text-gray-500" />
                                @elseif($viewIn === 'favorites')
                                    <x-filament::icon icon="heroicon-o-star" class="w-10 h-10 text-amber-400" />
                                @else
                                    <x-filament::icon icon="heroicon-o-cloud-arrow-up"
                                        class="w-10 h-10 text-primary-500 dark:text-primary-400" />
                                @endif
                            </div>
                        </div>

                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                            @if ($viewIn === 'trash')
                                {{ trans('filament-media::media.trash_empty') }}
                            @elseif($viewIn === 'favorites')
                                {{ trans('filament-media::media.no_favorites') }}
                            @elseif($search)
                                {{ trans('filament-media::media.no_search_results') }}
                            @else
                                {{ trans('filament-media::media.no_files') }}
                            @endif
                        </h3>

                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-sm">
                            @if ($viewIn === 'trash')
                                {{ trans('filament-media::media.trash_empty_description') }}
                            @elseif($viewIn === 'favorites')
                                {{ trans('filament-media::media.no_favorites_description') }}
                            @elseif($search)
                                {{ trans('filament-media::media.no_search_results_description') }}
                            @else
                                {{ trans('filament-media::media.drop_files_here') }}
                            @endif
                        </p>

                        @if ($viewIn === 'all_media' && !$search)
                            <button type="button"
                                x-on:click="$dispatch('open-upload-modal', { folderId: {{ $folderId }} })"
                                class="fm-upload-btn inline-flex items-center gap-2 px-6 py-3
                                    text-white font-medium rounded-xl transition-all duration-200">
                                <x-filament::icon icon="heroicon-m-arrow-up-tray" class="w-5 h-5" />
                                {{ trans('filament-media::media.upload_files') }}
                            </button>
                        @endif
                    </div>
                @else
                    {{-- Grid View --}}
                    @if ($viewType === 'grid')
                        <div
                            class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-8 gap-4">
                            @foreach ($this->items as $index => $item)
                                @include('filament-media::components.grid-item', [
                                    'item' => $item,
                                    'index' => $index,
                                ])
                            @endforeach
                        </div>
                    @else
                        {{-- List View --}}
                        <div class="fm-list-view">
                            {{-- List Header --}}
                            <div
                                class="hidden sm:grid grid-cols-12 gap-4 px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                                <div class="col-span-6">{{ trans('filament-media::media.name') }}</div>
                                <div class="col-span-2">{{ trans('filament-media::media.size') }}</div>
                                <div class="col-span-2">{{ trans('filament-media::media.type') }}</div>
                                <div class="col-span-2">{{ trans('filament-media::media.modified') }}</div>
                            </div>

                            {{-- List Items --}}
                            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($this->items as $index => $item)
                                    @include('filament-media::components.list-item', [
                                        'item' => $item,
                                        'index' => $index,
                                    ])
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            </main>

            {{-- Details Panel --}}
            @if ($showDetailsPanel)
                <aside
                    class="fm-details hidden lg:flex flex-col w-80 flex-shrink-0 border-l border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 overflow-hidden">
                    @if ($this->selectedItemDetails)
                        @include('filament-media::components.details-panel', [
                            'details' => $this->selectedItemDetails,
                        ])
                    @elseif(count($selectedItems) > 1)
                        {{-- Multiple Selection --}}
                        <div class="flex flex-col items-center justify-center h-full p-6 text-center">
                            <div
                                class="w-16 h-16 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center mb-4">
                                <span
                                    class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ count($selectedItems) }}</span>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">
                                {{ trans('filament-media::media.items_selected', ['count' => count($selectedItems)]) }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ trans('filament-media::media.select_one_for_details') }}
                            </p>
                        </div>
                    @else
                        {{-- No Selection --}}
                        <div class="flex flex-col items-center justify-center h-full p-6 text-center">
                            <div
                                class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
                                <x-filament::icon icon="heroicon-o-cursor-arrow-rays" class="w-8 h-8 text-gray-400" />
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">
                                {{ trans('filament-media::media.nothing_is_selected') }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ trans('filament-media::media.select_file_for_details') }}
                            </p>
                        </div>
                    @endif
                </aside>
            @endif
        </div>

    </div>

    {{-- Context Menu --}}
    <div x-show="contextMenu.show" x-cloak
        class="fm-context-menu fixed min-w-48 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 py-1 overflow-hidden"
        :style="`left: ${contextMenu.x}px; top: ${contextMenu.y}px;`" x-on:click.away="contextMenu.show = false"
        x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
        @include('filament-media::components.context-menu')
    </div>

    {{-- Loading Overlay --}}
    <div wire:loading.flex wire:target="navigateToFolder, setViewIn, setFilter, setSortBy, refresh"
        class="fixed inset-0 bg-white/50 dark:bg-gray-900/50 z-40 items-center justify-center">
        <x-filament::loading-indicator class="h-10 w-10" />
    </div>

    {{-- Upload Modal --}}
    @livewire('filament-media::upload-modal', ['folderId' => $folderId])

    {{-- Preview Modal --}}
    @livewire('filament-media::preview-modal')

    {{-- Filament Action Modals (required for mountAction to show modal dialogs) --}}
    <x-filament-actions::modals />
</x-filament-panels::page>
