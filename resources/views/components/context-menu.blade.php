{{-- Context Menu Items --}}
<div x-show="contextMenu.item" x-cloak>
    {{-- Open/Preview (hidden for missing files) --}}
    <button
        x-show="contextMenu.item && (contextMenu.item.is_folder || contextMenu.item.file_exists !== false)"
        type="button"
        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        x-on:click="if(contextMenu.item) { $wire.openItem({ id: contextMenu.item.id, is_folder: contextMenu.item.is_folder }); contextMenu.show = false; }"
    >
        <x-filament::icon icon="heroicon-m-eye" class="w-5 h-5 text-gray-400" />
        <span x-text="contextMenu.item?.is_folder ? '{{ trans('filament-media::media.open') }}' : '{{ trans('filament-media::media.preview') }}'"></span>
    </button>

    {{-- Divider - only show if Preview is visible (folder or existing file) --}}
    <div
        x-show="contextMenu.item && (contextMenu.item.is_folder || contextMenu.item.file_exists !== false)"
        class="border-t border-gray-200 dark:border-gray-700 my-1"
    ></div>

    {{-- Download (files only, and file must exist) --}}
    <a
        x-show="contextMenu.item && !contextMenu.item.is_folder && contextMenu.item.file_exists !== false"
        x-bind:href="contextMenu.item?.full_url || contextMenu.item?.url"
        download
        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        x-on:click="contextMenu.show = false"
    >
        <x-filament::icon icon="heroicon-m-arrow-down-tray" class="w-5 h-5 text-gray-400" />
        <span>{{ trans('filament-media::media.download') }}</span>
    </a>

    {{-- Copy Link (files only, and file must exist) --}}
    <button
        x-show="contextMenu.item && !contextMenu.item.is_folder && contextMenu.item.file_exists !== false"
        type="button"
        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        x-on:click="if(contextMenu.item) { window.FilamentMedia.download.copyToClipboard(contextMenu.item.full_url || contextMenu.item.url).then(() => { $dispatch('notify', { status: 'success', message: '{{ trans('filament-media::media.link_copied') }}' }) }); } contextMenu.show = false;"
    >
        <x-filament::icon icon="heroicon-m-link" class="w-5 h-5 text-gray-400" />
        <span>{{ trans('filament-media::media.copy_link') }}</span>
    </button>

    {{-- View Parent Details (linked model) --}}
    <button
        x-show="contextMenu.item && !contextMenu.item.is_folder && contextMenu.item.linked_model_label"
        type="button"
        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        x-on:click="if(contextMenu.item) { $wire.openParentDetailsModal([{ id: contextMenu.item.id, is_folder: false }]); contextMenu.show = false; }"
    >
        <x-filament::icon icon="heroicon-m-link" class="w-5 h-5 text-gray-400" />
        <span>{{ trans('filament-media::media.view_parent_details') }}</span>
    </button>

    {{-- Divider - only show if Download or Copy Link is visible (file exists) --}}
    <div
        x-show="contextMenu.item && !contextMenu.item.is_folder && contextMenu.item.file_exists !== false"
        class="border-t border-gray-200 dark:border-gray-700 my-1"
    ></div>

    {{-- Rename --}}
    <button
        type="button"
        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        x-on:click="if(contextMenu.item) { $wire.openRenameModal([{ id: contextMenu.item.id, is_folder: contextMenu.item.is_folder }]); contextMenu.show = false; }"
    >
        <x-filament::icon icon="heroicon-m-pencil" class="w-5 h-5 text-gray-400" />
        <span>{{ trans('filament-media::media.rename') }}</span>
        <span class="ml-auto text-xs text-gray-400">F2</span>
    </button>

    {{-- Move To --}}
    <button
        type="button"
        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        x-on:click="if(contextMenu.item) { $wire.openMoveModal([{ id: contextMenu.item.id, is_folder: contextMenu.item.is_folder }]); contextMenu.show = false; }"
    >
        <x-filament::icon icon="heroicon-m-arrow-right" class="w-5 h-5 text-gray-400" />
        <span>{{ trans('filament-media::media.move_to') }}</span>
    </button>

    {{-- Folder Properties (folders only) --}}
    <button
        x-show="contextMenu.item && contextMenu.item.is_folder"
        type="button"
        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        x-on:click="if(contextMenu.item) { $wire.openPropertiesModal([{ id: contextMenu.item.id, is_folder: true }]); contextMenu.show = false; }"
    >
        <x-filament::icon icon="heroicon-m-swatch" class="w-5 h-5 text-gray-400" />
        <span>{{ trans('filament-media::media.properties.name') }}</span>
    </button>

    {{-- Alt Text (images only) --}}
    <button
        x-show="contextMenu.item && !contextMenu.item.is_folder && contextMenu.item.type === 'image'"
        type="button"
        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        x-on:click="if(contextMenu.item) { $wire.openAltTextModal([{ id: contextMenu.item.id, is_folder: false }]); contextMenu.show = false; }"
    >
        <x-filament::icon icon="heroicon-m-chat-bubble-left" class="w-5 h-5 text-gray-400" />
        <span>{{ trans('filament-media::media.alt_text') }}</span>
    </button>

    {{-- Divider --}}
    <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>

    {{-- Manage Tags --}}
    @if(config('media.features.tags', true))
        <button
            type="button"
            class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            x-on:click="if(contextMenu.item) { $wire.openTagModal([{ id: contextMenu.item.id, is_folder: contextMenu.item.is_folder }]); contextMenu.show = false; }"
        >
            <x-filament::icon icon="heroicon-m-tag" class="w-5 h-5 text-gray-400" />
            <span>{{ trans('filament-media::media.manage_tags') }}</span>
        </button>
    @endif

    {{-- Add to Collection (files only) --}}
    @if(config('media.features.collections', true))
        <button
            x-show="contextMenu.item && !contextMenu.item.is_folder"
            type="button"
            class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            x-on:click="if(contextMenu.item) { $wire.openCollectionModal([{ id: contextMenu.item.id, is_folder: false }]); contextMenu.show = false; }"
        >
            <x-filament::icon icon="heroicon-m-rectangle-stack" class="w-5 h-5 text-gray-400" />
            <span>{{ trans('filament-media::media.add_to_collection') }}</span>
        </button>

        {{-- Remove from Collection (when browsing a collection) --}}
        <button
            x-show="contextMenu.item && !contextMenu.item.is_folder && viewIn === 'collections'"
            type="button"
            class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            x-on:click="if(contextMenu.item) { $wire.openRemoveFromCollectionModal([{ id: contextMenu.item.id, is_folder: false }]); contextMenu.show = false; }"
        >
            <x-filament::icon icon="heroicon-m-minus-circle" class="w-5 h-5 text-gray-400" />
            <span>{{ trans('filament-media::media.remove_from_collection') }}</span>
        </button>
    @endif

    {{-- Upload New Version (files only) --}}
    @if(config('media.features.versioning', true))
        <button
            x-show="contextMenu.item && !contextMenu.item.is_folder"
            type="button"
            class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            x-on:click="if(contextMenu.item) { $wire.openVersionModal([{ id: contextMenu.item.id, is_folder: false }]); contextMenu.show = false; }"
        >
            <x-filament::icon icon="heroicon-m-arrow-path" class="w-5 h-5 text-gray-400" />
            <span>{{ trans('filament-media::media.upload_new_version') }}</span>
        </button>
    @endif

    {{-- Edit Metadata (files only) --}}
    @if(config('media.features.metadata', true))
        <button
            x-show="contextMenu.item && !contextMenu.item.is_folder"
            type="button"
            class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            x-on:click="if(contextMenu.item) { $wire.openMetadataModal([{ id: contextMenu.item.id, is_folder: false }]); contextMenu.show = false; }"
        >
            <x-filament::icon icon="heroicon-m-document-text" class="w-5 h-5 text-gray-400" />
            <span>{{ trans('filament-media::media.edit_metadata') }}</span>
        </button>
    @endif

    {{-- Export (files only) --}}
    @if(config('media.features.export', true))
        <button
            x-show="contextMenu.item && !contextMenu.item.is_folder"
            type="button"
            class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            x-on:click="if(contextMenu.item) { $wire.openExportModal([{ id: contextMenu.item.id, is_folder: false }]); contextMenu.show = false; }"
        >
            <x-filament::icon icon="heroicon-m-arrow-up-on-square" class="w-5 h-5 text-gray-400" />
            <span>{{ trans('filament-media::media.export') }}</span>
        </button>
    @endif

    {{-- Divider --}}
    <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>

    {{-- Add to Favorites (when NOT in favorites view) --}}
    <button
        x-show="viewIn !== 'favorites'"
        type="button"
        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        x-on:click="if(contextMenu.item) { $wire.openFavoriteModal([{ id: contextMenu.item.id, is_folder: contextMenu.item.is_folder }]); contextMenu.show = false; }"
    >
        <x-filament::icon icon="heroicon-m-star" class="w-5 h-5 text-gray-400" />
        <span>{{ trans('filament-media::media.add_to_favorites') }}</span>
    </button>

    {{-- Remove from Favorites (when in favorites view) --}}
    <button
        x-show="viewIn === 'favorites'"
        type="button"
        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        x-on:click="if(contextMenu.item) { $wire.openRemoveFavoriteModal([{ id: contextMenu.item.id, is_folder: contextMenu.item.is_folder }]); contextMenu.show = false; }"
    >
        <x-filament::icon icon="heroicon-m-star" class="w-5 h-5 text-amber-500" />
        <span>{{ trans('filament-media::media.remove_from_favorites') }}</span>
    </button>

    {{-- Divider --}}
    <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>

    {{-- Move to Trash --}}
    <button
        type="button"
        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
        x-on:click="if(contextMenu.item) { $wire.openTrashModal([{ id: contextMenu.item.id, is_folder: contextMenu.item.is_folder }]); contextMenu.show = false; }"
    >
        <x-filament::icon icon="heroicon-m-trash" class="w-5 h-5" />
        <span>{{ trans('filament-media::media.move_to_trash') }}</span>
        <span class="ml-auto text-xs opacity-60">Del</span>
    </button>
</div>
