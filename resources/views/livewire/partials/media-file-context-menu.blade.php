@php
    $show = fn(string $key) => $contextMenu && !in_array($key, $contextMenuExclude);
@endphp

@if ($contextMenu)
    <div x-show="contextMenu.show" x-cloak
        class="fixed min-w-48 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 py-1 overflow-hidden z-50"
        :style="`left: ${contextMenu.x}px; top: ${contextMenu.y}px;`" x-on:click.away="closeContextMenu()"
        x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

        {{-- Preview --}}
        @if ($show('preview'))
            <a :href="contextMenu.item?.url" target="_blank"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                x-on:click="closeContextMenu()">
                <x-filament::icon icon="heroicon-m-eye" class="w-5 h-5 text-gray-900 dark:text-gray-400" />
                <span>{{ trans('filament-media::media.preview') }}</span>
            </a>
        @endif

        {{-- Download --}}
        @if ($show('download'))
            <a :href="contextMenu.item?.url" download
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                x-on:click="closeContextMenu()">
                <x-filament::icon icon="heroicon-m-arrow-down-tray"
                    class="w-5 h-5 text-gray-900 dark:text-gray-400" />
                <span>{{ trans('filament-media::media.download') }}</span>
            </a>
        @endif

        {{-- Copy Link --}}
        @if ($show('copy_link'))
            <button type="button"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                x-on:click="
                    if (window.FilamentMedia?.download) {
                        window.FilamentMedia.download.copyToClipboard(contextMenu.item?.url).then(() => {
                            $dispatch('notify', { status: 'success', message: '{{ trans('filament-media::media.link_copied') }}' });
                        });
                    } else {
                        navigator.clipboard.writeText(contextMenu.item?.url);
                    }
                    closeContextMenu();
                ">
                <x-filament::icon icon="heroicon-m-link" class="w-5 h-5 text-gray-900 dark:text-gray-400" />
                <span>{{ trans('filament-media::media.copy_link') }}</span>
            </button>
        @endif

        {{-- View Parent Details (only when file has a linked model) --}}
        @if ($show('view_parent'))
            <button x-show="contextMenu.item?.linked_model_label" type="button"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                x-on:click="$wire.openParentDetailsModal([{ id: contextMenu.item.id, is_folder: false }]); closeContextMenu();">
                <x-filament::icon icon="heroicon-m-link" class="w-5 h-5 text-gray-900 dark:text-gray-400" />
                <span>{{ trans('filament-media::media.view_parent_details') }}</span>
            </button>
        @endif

        @if ($show('preview') || $show('download') || $show('copy_link'))
            <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
        @endif

        {{-- Rename --}}
        @if ($show('rename'))
            <button type="button"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                x-on:click="$wire.openRenameModal([{ id: contextMenu.item.id, is_folder: false }]); closeContextMenu();">
                <x-filament::icon icon="heroicon-m-pencil"
                    class="w-5 h-5 text-gray-900 dark:text-gray-400" />
                <span>{{ trans('filament-media::media.rename') }}</span>
            </button>
        @endif

        {{-- Alt Text (images only) --}}
        @if ($show('alt_text'))
            <button x-show="contextMenu.item?.type === 'image'" type="button"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                x-on:click="$wire.openAltTextModal([{ id: contextMenu.item.id, is_folder: false }]); closeContextMenu();">
                <x-filament::icon icon="heroicon-m-chat-bubble-left"
                    class="w-5 h-5 text-gray-900 dark:text-gray-400" />
                <span>{{ trans('filament-media::media.alt_text') }}</span>
            </button>
        @endif

        {{-- Manage Tags --}}
        @if ($show('tags') && config('media.features.tags', true))
            <button type="button"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                x-on:click="$wire.openTagModal([{ id: contextMenu.item.id, is_folder: false }]); closeContextMenu();">
                <x-filament::icon icon="heroicon-m-tag" class="w-5 h-5 text-gray-900 dark:text-gray-400" />
                <span>{{ trans('filament-media::media.manage_tags') }}</span>
            </button>
        @endif

        {{-- Add to Collection --}}
        @if ($show('collections') && config('media.features.collections', true))
            <button type="button"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                x-on:click="$wire.openCollectionModal([{ id: contextMenu.item.id, is_folder: false }]); closeContextMenu();">
                <x-filament::icon icon="heroicon-m-rectangle-stack"
                    class="w-5 h-5 text-gray-900 dark:text-gray-400" />
                <span>{{ trans('filament-media::media.add_to_collection') }}</span>
            </button>
        @endif

        {{-- Upload New Version --}}
        @if ($show('versions') && config('media.features.versioning', true))
            <button type="button"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                x-on:click="$wire.openVersionModal([{ id: contextMenu.item.id, is_folder: false }]); closeContextMenu();">
                <x-filament::icon icon="heroicon-m-arrow-path"
                    class="w-5 h-5 text-gray-900 dark:text-gray-400" />
                <span>{{ trans('filament-media::media.upload_new_version') }}</span>
            </button>
        @endif

        {{-- Edit Metadata --}}
        @if ($show('metadata') && config('media.features.metadata', true))
            <button type="button"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                x-on:click="$wire.openMetadataModal([{ id: contextMenu.item.id, is_folder: false }]); closeContextMenu();">
                <x-filament::icon icon="heroicon-m-document-text"
                    class="w-5 h-5 text-gray-900 dark:text-gray-400" />
                <span>{{ trans('filament-media::media.edit_metadata') }}</span>
            </button>
        @endif

        {{-- Export --}}
        @if ($show('export') && config('media.features.export', true))
            <button type="button"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                x-on:click="$wire.openExportModal([{ id: contextMenu.item.id, is_folder: false }]); closeContextMenu();">
                <x-filament::icon icon="heroicon-m-arrow-up-on-square"
                    class="w-5 h-5 text-gray-900 dark:text-gray-400" />
                <span>{{ trans('filament-media::media.export') }}</span>
            </button>
        @endif

        {{-- Change Visibility --}}
        @if ($show('visibility'))
            <button type="button"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                x-on:click="$wire.mountAction('change_visibility', { items: [{ id: contextMenu.item.id, is_folder: false }] }); closeContextMenu();">
                <x-filament::icon icon="heroicon-m-eye"
                    class="w-5 h-5 text-gray-900 dark:text-gray-400" />
                <span>{{ trans('filament-media::media.change_visibility') }}</span>
            </button>
        @endif

        {{-- Divider --}}
        <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>

        {{-- Add to Favorites (when item is NOT favorited) --}}
        @if ($show('favorites'))
            <button x-show="contextMenu.item && !contextMenu.item.is_favorited" type="button"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                x-on:click="$wire.openFavoriteModal([{ id: contextMenu.item.id, is_folder: false }]); closeContextMenu();">
                <x-filament::icon icon="heroicon-m-star"
                    class="w-5 h-5 text-gray-900 dark:text-gray-400" />
                <span>{{ trans('filament-media::media.add_to_favorites') }}</span>
            </button>

            {{-- Remove from Favorites (when item IS favorited) --}}
            <button x-show="contextMenu.item && contextMenu.item.is_favorited" type="button"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                x-on:click="$wire.openRemoveFavoriteModal([{ id: contextMenu.item.id, is_folder: false }]); closeContextMenu();">
                <x-filament::icon icon="heroicon-m-star" class="w-5 h-5 text-amber-500" />
                <span>{{ trans('filament-media::media.remove_from_favorites') }}</span>
            </button>
        @endif

        {{-- Move to Trash --}}
        @if ($show('trash') && $deletable)
            <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
            <button type="button"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                x-on:click="$wire.openTrashModal([{ id: contextMenu.item.id, is_folder: false }]); closeContextMenu();">
                <x-filament::icon icon="heroicon-m-trash" class="w-5 h-5" />
                <span>{{ trans('filament-media::media.move_to_trash') }}</span>
            </button>
        @endif
    </div>
@endif
