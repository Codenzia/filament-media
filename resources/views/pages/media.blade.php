<x-filament-panels::page x-data="{
    ctrlKey: false,
    shiftKey: false,
    lastSelectedIndex: null,
    contextMenu: { show: false, x: 0, y: 0, item: null },
    bgContextMenu: { show: false, x: 0, y: 0 },
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
                this.bgContextMenu.show = false;
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
        class="fm-container flex flex-col min-h-[calc(100vh-12rem)] bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">

        {{-- Top Toolbar - Reorganized Layout --}}
        <header
            class="fm-toolbar flex-shrink-0 flex flex-wrap items-center justify-between gap-3 p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
            {{-- LEFT side: Search, View In, Filter, Refresh --}}
            <div class="flex items-center gap-3">
                {{-- Search --}}
                <div class="relative">
                    <x-filament::input.wrapper>
                        <x-filament::input type="search" wire:model.live.debounce.300ms="search"
                            placeholder="{{ trans('filament-media::media.search_in_current_folder') }}"
                            class="w-64" />
                    </x-filament::input.wrapper>
                </div>

                {{-- View In Dropdown (All media) --}}
                @php
                    $viewIcon = match ($viewIn) {
                        'trash' => 'heroicon-m-trash',
                        'recent' => 'heroicon-m-clock',
                        'favorites' => 'heroicon-m-star',
                        'collections' => 'heroicon-m-rectangle-stack',
                        default => 'heroicon-m-photo',
                    };
                    $viewLabel = match ($viewIn) {
                        'trash' => trans('filament-media::media.trash'),
                        'recent' => trans('filament-media::media.recent'),
                        'favorites' => trans('filament-media::media.favorites'),
                        'collections' => trans('filament-media::media.collections'),
                        default => trans('filament-media::media.all_media'),
                    };
                    $viewBadgeColor = match ($viewIn) {
                        'trash' => 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300',
                        'recent' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300',
                        'favorites' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
                        'collections' => 'bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300',
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

                    @if (config('media.features.collections', true))
                        <x-filament::dropdown.list.item icon="heroicon-m-rectangle-stack"
                            wire:click="setViewIn('collections')" :color="$viewIn === 'collections' ? 'primary' : 'gray'">
                            {{ trans('filament-media::media.collections') }}
                        </x-filament::dropdown.list.item>
                    @endif

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
                    $filterBadgeColor =
                        $filter !== 'everything'
                            ? 'bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-300'
                            : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
                @endphp
                <x-filament-media::toolbar-dropdown :icon="$filterIcon" :label="$filterLabel" :badge-color="$filterBadgeColor">
                    <x-filament::dropdown.list.item icon="heroicon-m-squares-2x2" wire:click="setFilter('everything')"
                        :color="$filter === 'everything' ? 'primary' : 'gray'">
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

                    <x-filament::dropdown.list.item icon="heroicon-m-document-text" wire:click="setFilter('document')"
                        :color="$filter === 'document' ? 'primary' : 'gray'">
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
                {{-- Sort Dropdown (Alpine-only, no Filament x-float) --}}
                @php
                    $sortIcon = str_ends_with($sortBy, '-desc') ? 'heroicon-m-arrow-down' : 'heroicon-m-arrow-up';
                    $sortLabel = $sorts[$sortBy]['label'] ?? trans('filament-media::media.uploaded_date_desc');
                @endphp
                <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                    <button type="button" class="fm-dropdown-trigger" x-on:click="open = !open">
                        <x-filament::icon :icon="$sortIcon" class="fm-dropdown-icon" />
                        <span
                            class="fm-dropdown-badge bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $sortLabel }}</span>
                        <x-filament::icon icon="heroicon-m-chevron-down" class="fm-dropdown-chevron" />
                    </button>

                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        class="fm-sort-panel absolute right-0 top-full mt-1 w-56 rounded-lg bg-white dark:bg-gray-900 shadow-lg ring-1 ring-gray-200 dark:ring-gray-700 py-1">
                        @foreach ($sorts as $key => $sort)
                            <button type="button" wire:click="setSortBy('{{ $key }}')"
                                x-on:click="open = false"
                                class="flex items-center gap-2 w-full px-3 py-2 text-sm text-left transition-colors
                                    {{ $sortBy === $key
                                        ? 'text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-500/10'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                                <x-filament::icon :icon="$sort['icon']" class="w-4 h-4" />
                                {{ $sort['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>

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

                {{-- Details Panel Toggle --}}
                <div class="w-px h-5 bg-gray-300 dark:bg-gray-600"></div>
                <button type="button" wire:click="toggleDetailsPanel"
                    class="p-2 rounded-md transition-all duration-200
                        {{ $showDetailsPanel
                            ? 'text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-500/10'
                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}"
                    title="{{ trans('filament-media::media.toggle_details') }}">
                    <x-filament::icon icon="heroicon-m-information-circle" class="w-4 h-4" />
                </button>
            </div>
        </header>

        {{-- Breadcrumbs --}}
        <nav
            class="fm-breadcrumbs flex-shrink-0 flex flex-col gap-0.5 px-4 py-2 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm">
            <div class="flex items-center gap-2 overflow-x-auto">
            @foreach ($this->breadcrumbs as $index => $crumb)
                @if ($index > 0)
                    <x-filament::icon icon="heroicon-m-chevron-right" class="w-4 h-4 text-gray-900 dark:text-gray-400 flex-shrink-0" />
                @endif

                <button type="button" wire:click="navigateToFolder({{ $crumb['id'] }})"
                    class="flex items-center gap-1.5 text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors whitespace-nowrap {{ $loop->last ? 'font-medium text-gray-900 dark:text-white' : '' }}">
                    @if (isset($crumb['icon']))
                        {!! $crumb['icon'] !!}
                    @endif
                    <span>{{ $crumb['name'] }}</span>
                </button>
            @endforeach

            <div class="flex-1"></div>

            {{-- Breadcrumb actions menu (only in all_media view) --}}
            <div class="flex-shrink-0" x-show="$wire.viewIn === 'all_media'" x-cloak
                x-data="{ open: false, dropdownX: 0, dropdownY: 0 }"
                x-on:click.outside="open = false">
                <button type="button" x-ref="breadcrumbMenuBtn"
                    x-on:click="
                        const rect = $refs.breadcrumbMenuBtn.getBoundingClientRect();
                        dropdownX = rect.right - 192;
                        dropdownY = rect.bottom + 4;
                        open = !open;
                    "
                    class="w-7 h-7 rounded-md flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <x-filament::icon icon="heroicon-m-ellipsis-vertical" class="w-4 h-4" />
                </button>

                <template x-teleport="body">
                    <div x-show="open" x-cloak
                        x-on:click.outside="open = false"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="fixed w-48 rounded-lg bg-white dark:bg-gray-900 shadow-lg ring-1 ring-gray-200 dark:ring-gray-700 py-1"
                        :style="`left: ${dropdownX}px; top: ${dropdownY}px; z-index: 200;`">
                        <button type="button"
                            x-on:click="open = false; $wire.mountAction('create_folder')"
                            class="flex items-center gap-2 w-full px-3 py-2 text-sm text-left text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <x-filament::icon icon="heroicon-m-folder-plus" class="w-4 h-4" style="color: var(--fm-icon-color)" />
                            {{ trans('filament-media::media.create_folder') }}
                        </button>
                    </div>
                </template>
            </div>
            </div>

            {{-- File count line --}}
            @php
                $fileCount = $this->items->where('is_folder', false)->count();
            @endphp
            @if ($fileCount > 0)
                <div class="text-xs tabular-nums text-gray-400 dark:text-gray-500"
                    wire:key="file-count-{{ $folderId }}-{{ $viewIn }}-{{ $filter }}-{{ $fileCount }}">
                    {{ trans('filament-media::media.total_files', ['count' => $fileCount]) }}
                </div>
            @endif
        </nav>

        {{-- Main Content Area --}}
        <div class="fm-content flex flex-1 overflow-hidden">
            {{-- File Browser --}}
            <main x-data="{ isDropZone: false }" class="fm-browser flex-1 overflow-y-auto p-4 relative"
                :class="{ 'fm-drag-over': isDropZone }" wire:loading.class="opacity-50"
                x-on:contextmenu.prevent="
                    if (!$event.target.closest('.fm-item')) {
                        $wire.clearSelection();
                        contextMenu.show = false;
                        if ($wire.viewIn === 'all_media') {
                            bgContextMenu = { show: true, x: $event.clientX, y: $event.clientY };
                        }
                    }
                "
                x-on:dragover="
                    if ($event.dataTransfer.types.includes('Files')) {
                        $event.preventDefault();
                        isDropZone = true;
                        $event.dataTransfer.dropEffect = 'copy';
                    }
                "
                x-on:dragleave="isDropZone = false"
                x-on:drop.prevent="
                    isDropZone = false;
                    if ($event.dataTransfer.files.length > 0) {
                        $dispatch('upload-dropped-files', { folderId: {{ $folderId }}, files: $event.dataTransfer.files });
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
                @if ($viewIn === 'collections' && $collectionId === 0)
                    {{-- Collection Picker --}}
                    @if ($allCollections->isEmpty())
                        <div class="fm-empty-state flex flex-col items-center justify-center h-full text-center py-16">
                            <div class="fm-empty-icon relative mb-6">
                                <div
                                    class="w-20 h-20 rounded-full bg-gradient-to-br from-purple-100 to-purple-200 dark:from-purple-900/40 dark:to-purple-800/40 flex items-center justify-center shadow-lg shadow-purple-500/20">
                                    <x-filament::icon icon="heroicon-o-rectangle-stack"
                                        class="w-10 h-10 text-purple-400" />
                                </div>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                {{ trans('filament-media::media.no_collections_yet') }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-sm">
                                {{ trans('filament-media::media.no_collections_yet_description') }}
                            </p>
                        </div>
                    @else
                        <div
                            class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                            @foreach ($allCollections as $collection)
                                <button type="button" wire:click="setCollection({{ $collection->id }})"
                                    class="fm-item group flex flex-col items-center gap-3 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-primary-300 dark:hover:border-primary-600 cursor-pointer text-center">
                                    <div
                                        class="w-14 h-14 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center group-hover:bg-purple-200 dark:group-hover:bg-purple-900/50 transition-colors">
                                        <x-filament::icon icon="heroicon-o-rectangle-stack"
                                            class="w-7 h-7 text-purple-500 dark:text-purple-400" />
                                    </div>
                                    <div class="min-w-0 w-full">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            {{ $collection->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ trans('filament-media::media.collection_file_count', ['count' => $collection->files_count]) }}
                                        </p>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif
                @elseif ($this->items->isEmpty())
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
                                        class="w-10 h-10 text-gray-900 dark:text-gray-400" />
                                @elseif($viewIn === 'favorites')
                                    <x-filament::icon icon="heroicon-o-star" class="w-10 h-10 text-amber-400" />
                                @elseif($viewIn === 'collections')
                                    <x-filament::icon icon="heroicon-o-rectangle-stack"
                                        class="w-10 h-10 text-purple-400" />
                                @else
                                    <x-filament::icon icon="heroicon-o-cloud-arrow-up"
                                        class="w-10 h-10 text-gray-900 dark:text-primary-400" />
                                @endif
                            </div>
                        </div>

                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                            @if ($viewIn === 'trash')
                                {{ trans('filament-media::media.trash_empty') }}
                            @elseif($viewIn === 'favorites')
                                {{ trans('filament-media::media.no_favorites') }}
                            @elseif($viewIn === 'collections')
                                {{ trans('filament-media::media.collection_empty') }}
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
                            @elseif($viewIn === 'collections')
                                {{ trans('filament-media::media.collection_empty_description') }}
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
                            class="grid gap-2"
                            style="grid-template-columns: repeat(auto-fill, minmax(130px, 1fr))">
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

                    {{-- Infinite Scroll Sentinel --}}
                    @if ($hasMorePages)
                        <div x-intersect.margin.200px="$wire.loadMore()"
                            class="flex justify-center py-4">
                            <x-filament::loading-indicator
                                wire:loading wire:target="loadMore"
                                class="h-6 w-6 text-gray-400" />
                        </div>
                    @endif
                @endif
            </main>

            {{-- Details Panel --}}
            @if ($showDetailsPanel)
                <aside
                    class="fm-details flex flex-col w-80 flex-shrink-0 border-l border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 overflow-hidden">
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
                                <x-filament::icon icon="heroicon-o-cursor-arrow-rays" class="w-8 h-8 text-gray-900 dark:text-gray-400" />
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

    {{-- Background Context Menu (right-click on empty grid space) --}}
    <div x-show="bgContextMenu.show" x-cloak
        class="fm-context-menu fixed min-w-48 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 py-1 overflow-hidden"
        :style="`left: ${bgContextMenu.x}px; top: ${bgContextMenu.y}px;`"
        x-on:click.away="bgContextMenu.show = false"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95">
        <button type="button"
            x-on:click="bgContextMenu.show = false; $wire.mountAction('create_folder')"
            class="flex items-center gap-2.5 w-full px-3 py-2 text-sm text-left text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
            <x-filament::icon icon="heroicon-m-folder-plus" class="w-5 h-5 text-gray-900 dark:text-gray-400" />
            {{ trans('filament-media::media.create_folder_here') }}
        </button>
    </div>

    {{-- Loading Overlay --}}
    <div wire:loading.flex wire:target="navigateToFolder, setViewIn, setCollection, setFilter, setSortBy, refresh"
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
