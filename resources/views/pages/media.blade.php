<x-filament-panels::page>
    <div>
        <div id="media-content" wire:ignore>

            <div class="fm-media-container min-h-screen flex flex-col">
                <div class="fm-media-wrapper">
                    <input
                        type="checkbox"
                        id="media_details_collapse"
                        class="hidden fake-click-event"
                    >

                    <div class="fm-media-main-wrapper flex-1 flex flex-col h-full">
                        <div class="flex flex-col fm-media-header p-0">
                            <div class="w-full fm-media-top-header flex flex-wrap gap-3 justify-between items-start">
                                <div class="flex flex-wrap gap-3 py-2 justify-between w-full md:w-auto fm-media-actions rounded-xl shadow-sm items-center">
                                    {{-- <x-filament::icon-button
                                        class="flex md:hidden bg-white dark:bg-gray-900"
                                        icon="heroicon-m-bars-3"
                                        data-bs-toggle="offcanvas"
                                        href="#fm-media-aside"
                                        :label="trans('filament-media::media.menu_name')"
                                        color="gray"
                                        size="sm"
                                    /> --}}

                                    <div class="fm-media-actions__controls flex flex-wrap items-center gap-3 py-3">
                                        <x-filament::dropdown class="md:block bg-white text-gray-900 dark:bg-gray-900 dark:text-white rounded-lg">
                                            <x-slot:trigger>
                                                <x-filament::button
                                                    type="button"
                                                    icon="heroicon-m-arrow-up-tray"
                                                    color="primary"
                                                    size="lg"
                                                    class="bg-white dark:bg-gray-900 dark:text-white text-gray-900 media-icon-button"
                                                    :label="trans('filament-media::media.upload')"
                                                >
                                                    {{ trans('filament-media::media.upload') }}
                                                </x-filament::button>
                                            </x-slot:trigger>
                                            <x-filament::dropdown.list>
                                                <x-filament::dropdown.list.item
                                                    icon="heroicon-m-globe-alt"
                                                    class="js-dropzone-upload"
                                                    data-type="view_in"
                                                    data-value="all_media"
                                                >
                                                    {{ trans('filament-media::media.upload_from_local') }}
                                                </x-filament::dropdown.list.item>

                                                <x-filament::dropdown.list.item
                                                    icon="heroicon-m-trash"
                                                    wire:click="mountAction('download_url')"
                                                >
                                                    {{ trans('filament-media::media.upload_from_url') }}
                                                </x-filament::dropdown.list.item>
                                            </x-filament::dropdown.list>

                                        </x-filament::dropdown>

                                        <x-filament::button
                                            type="button"
                                            color="primary"
                                            :tooltip="trans('filament-media::media.create_folder')"
                                            class="bg-white dark:bg-gray-900 dark:text-white text-gray-900 media-icon-button"
                                            wire:click="mountAction('create_folder')"
                                            icon="heroicon-m-folder-plus"
                                            :label="trans('filament-media::media.create_folder')"
                                            size="lg"
                                        >
                                            {{ trans('filament-media::media.create_folder') }}
                                        </x-filament::button>

                                        <x-filament::button
                                            type="button"
                                            color="primary"
                                            :tooltip="trans('filament-media::media.refresh')"
                                            class="js-change-action bg-white dark:bg-gray-900 dark:text-white text-gray-900 media-icon-button"
                                            icon="heroicon-m-arrow-path"
                                            data-type="refresh"
                                            :label="trans('filament-media::media.refresh')"
                                            size="lg"
                                        >
                                            {{ trans('filament-media::media.refresh') }}
                                        </x-filament::button>

                                        @if (FilamentMedia::getConfig('sidebar_display') !== 'vertical')
                                            <x-filament::dropdown>
                                                <x-slot:trigger>
                                                    <x-filament::button
                                                        type="button"
                                                        color="primary"
                                                        icon="heroicon-m-funnel"
                                                        class="js-fm-media-change-filter-group js-filter-by-type bg-white dark:bg-gray-900 dark:text-white text-gray-900 media-icon-button"
                                                        :tooltip="trans('filament-media::media.filter')"
                                                        :label="trans('filament-media::media.filter')"
                                                        size="lg"
                                                    >
                                                    {{ trans('filament-media::media.filter') }} <span class="js-fm-media-filter-current"></span>
                                                    </x-filament::button>
                                                </x-slot:trigger>
                                                <x-filament::dropdown.list>
                                                    <x-filament::dropdown.list.item
                                                        icon="heroicon-m-arrow-path"
                                                        class="js-fm-media-change-filter"
                                                        data-type="filter"
                                                        data-value="everything"
                                                    >
                                                        {{ trans('filament-media::media.everything') }}
                                                    </x-filament::dropdown.list.item>

                                                    @if (array_key_exists('image', FilamentMedia::getConfig('mime_types', [])))
                                                        <x-filament::dropdown.list.item
                                                            icon="heroicon-m-photo"
                                                            class="js-fm-media-change-filter"
                                                            data-type="filter"
                                                            data-value="image"
                                                        >
                                                            {{ trans('filament-media::media.image') }}
                                                        </x-filament::dropdown.list.item>
                                                    @endif

                                                    @if (array_key_exists('video', FilamentMedia::getConfig('mime_types', [])))
                                                        <x-filament::dropdown.list.item
                                                            icon="heroicon-m-film"
                                                            class="js-fm-media-change-filter"
                                                            data-type="filter"
                                                            data-value="video"
                                                        >
                                                            {{ trans('filament-media::media.video') }}
                                                        </x-filament::dropdown.list.item>
                                                    @endif

                                                    <x-filament::dropdown.list.item
                                                        icon="heroicon-m-document-text"
                                                        class="js-fm-media-change-filter"
                                                        data-type="filter"
                                                        data-value="document"
                                                    >
                                                        {{ trans('filament-media::media.document') }}
                                                    </x-filament::dropdown.list.item>
                                                </x-filament::dropdown.list>
                                            </x-filament::dropdown>

                                            <x-filament::dropdown>
                                                <x-slot:trigger>
                                                    <x-filament::button
                                                        type="button"
                                                        color="primary"
                                                        icon="heroicon-m-eye"
                                                        class="js-fm-media-change-filter-group js-filter-by-view-in bg-white dark:bg-gray-900 dark:text-white text-gray-900 media-icon-button"
                                                        :tooltip="trans('filament-media::media.view_in')"
                                                        :label="trans('filament-media::media.view_in')"
                                                        size="lg"
                                                    >
                                                        {{ trans('filament-media::media.view_in') }} <span class="js-fm-media-filter-current"></span>
                                                    </x-filament::button>
                                                </x-slot:trigger>

                                                <x-filament::dropdown.list>
                                                    <x-filament::dropdown.list.item
                                                        icon="heroicon-m-globe-alt"
                                                        class="js-fm-media-change-filter"
                                                        data-type="view_in"
                                                        data-value="all_media"
                                                    >
                                                        {{ trans('filament-media::media.all_media') }}
                                                    </x-filament::dropdown.list.item>

                                                    <x-filament::dropdown.list.item
                                                        icon="heroicon-m-trash"
                                                        class="js-fm-media-change-filter"
                                                        data-type="view_in"
                                                        data-value="trash"
                                                    >
                                                        {{ trans('filament-media::media.trash') }}
                                                    </x-filament::dropdown.list.item>

                                                    <x-filament::dropdown.list.item
                                                        icon="heroicon-m-clock"
                                                        class="js-fm-media-change-filter"
                                                        data-type="view_in"
                                                        data-value="recent"
                                                    >
                                                        {{ trans('filament-media::media.recent') }}
                                                    </x-filament::dropdown.list.item>

                                                    <x-filament::dropdown.list.item
                                                        icon="heroicon-m-star"
                                                        class="js-fm-media-change-filter"
                                                        data-type="view_in"
                                                        data-value="favorites"
                                                    >
                                                        {{ trans('filament-media::media.favorites') }}
                                                    </x-filament::dropdown.list.item>
                                                </x-filament::dropdown.list>
                                            </x-filament::dropdown>
                                        @endif

                                        <x-filament::button
                                            type="button"
                                            color="danger"
                                            class="hidden flex js-files-action fm-media-actions bg-red-500 dark:bg-red-500/10 dark:text-red-500 text-red-500 media-icon-button"
                                            data-action="empty_trash"
                                            icon="heroicon-m-trash"
                                            :label="trans('filament-media::media.empty_trash')"
                                            :tooltip="trans('filament-media::media.empty_trash')"
                                            size="lg"
                                        >
                                            {{ trans('filament-media::media.empty_trash') }}
                                        </x-filament::button>
                                    </div>
                                    <div class="fm-media-search">
                                        <form
                                            class="flex items-center"
                                            action=""
                                            method="GET"
                                        >
                                            <x-filament::input
                                                type="search"
                                                name="search"
                                                class="w-36 bg-white dark:bg-gray-900 border border-r-0 rounded-r-none border-l-0 border-transparent rounded-l-none h-10"
                                                placeholder="{{ trans('filament-media::media.search_file_and_folder') }}"
                                            />
                                            <x-filament::button
                                                type="submit"
                                                color="primary"
                                                icon="heroicon-m-magnifying-glass"
                                                size="lg"
                                                class="bg-white dark:bg-gray-900 border border-l-0 border-transparent h-10 dark:text-white text-gray-900 media-icon-button"
                                            >
                                            </x-filament::button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="flex w-full p-2 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 justify-between">
                                <div class="flex-1 p-2 flex items-center fm-media-breadcrumb">
                                    <ul class="breadcrumb flex flex-wrap items-center gap-2 text-sm text-gray-500">
                                        <li>
                                            <a href="#" data-folder="0" class="no-underline hover:text-primary-500 js-change-folder flex items-center gap-2">
                                                <x-filament::icon icon="heroicon-m-photo" class="w-5 h-5" />
                                                All media
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="flex-1 w-full md:w-auto flex justify-end items-center fm-media-tools gap-2 flex-wrap">
                                    <div
                                        class="flex items-center gap-2"
                                        role="group"
                                    >
                                        <x-filament::dropdown>
                                            <x-slot:trigger>
                                                <x-filament::button
                                                    icon="heroicon-m-document-arrow-up"
                                                    size="lg"
                                                    class="px-4 py-2 shadow-sm bg-transparent dark:bg-gray-900 dark:text-white text-gray-900 media-icon-button"
                                                >
                                                    {{ trans('filament-media::media.sort') }}
                                                </x-filament::button>
                                            </x-slot:trigger>

                                            <x-filament::dropdown.list>
                                                @foreach ($this->sorts as $key => $item)
                                                <x-filament::dropdown.list.item
                                                    :label="$item['label']"
                                                    :icon="$item['icon']"
                                                    class="js-fm-media-change-filter"
                                                    data-type="sort_by"
                                                    :data-value="$key"
                                                >
                                                {{ $item['label'] }}
                                                </x-filament::dropdown.list.item>
                                                @endforeach
                                            </x-filament::dropdown.list>
                                        </x-filament::dropdown>

                                        <x-filament::dropdown class="fm-dropdown-actions hidden">
                                            <x-slot:trigger>
                                                <x-filament::button
                                                    icon="heroicon-m-hand-raised"
                                                    class="fm-dropdown-actions"
                                                    disabled
                                                    outlined
                                                >
                                                    {{ trans('filament-media::media.actions') }}
                                                </x-filament::button>
                                            </x-slot:trigger>

                                            <x-filament::dropdown.list class="fm-dropdown-actions-list" />
                                        </x-filament::dropdown>
                                    </div>
                                    <div
                                        class="inline-flex rounded-md shadow-sm isolate js-fm-media-change-view-type ms-2 flex items-center gap-2"
                                        role="group"
                                    >
                                        <x-filament::icon-button
                                            type="button"
                                            data-type="tiles"
                                            icon="heroicon-m-squares-2x2"
                                            :label="trans('filament-media::media.view_type') ?? 'Tiles'"
                                            size="lg"
                                            class="shadow-sm"
                                        />
                                        <x-filament::icon-button
                                            type="button"
                                            data-type="list"
                                            icon="heroicon-m-list-bullet"
                                            :label="trans('filament-media::media.view_type') ?? 'List'"
                                            size="lg"
                                            class="shadow-sm"
                                        />
                                    </div>
                                    <x-filament::icon-button
                                        tag="label"
                                        for="media_details_collapse"
                                        class="collapse-panel ms-2 hidden lg:flex shadow-sm"
                                        icon="heroicon-m-chevron-double-right"
                                        :label="trans('filament-media::media.details') ?? 'Toggle details'"
                                        size="lg"
                                    />
                                </div>
                            </div>
                        </div>

                        <main class="fm-media-main">
                            <div class="fm-media-items"></div>
                            <div class="fm-media-details" style="display: none">
                                <div class="fm-media-thumbnail">
                                    <x-filament::icon icon="heroicon-m-photo" />
                                </div>
                                <div class="fm-media-description">
                                    <div class="fm-media-name">
                                        <p>{{ trans('filament-media::media.nothing_is_selected') }}</p>
                                    </div>
                                </div>
                            </div>
                        </main>
                        <footer class="hidden fm-media-footer">
                            <x-filament::icon-button
                                type="button"
                                color="primary"
                                class="js-insert-to-editor"
                                icon="heroicon-m-check"
                                :label="trans('filament-media::media.insert')"
                                :tooltip="trans('filament-media::media.insert')"
                            />
                        </footer>
                    </div>
                    <div class="fm-upload-progress hide-the-pane fixed bottom-0 end-0 z-50 m-6 w-96 max-w-full">
                        <x-filament::section compact>
                            <x-slot name="heading">
                                <div class="flex items-center justify-between gap-4">
                                    <span>{{ trans('filament-media::media.upload_progress') }}</span>
                                    <x-filament::icon-button
                                        icon="heroicon-m-x-mark"
                                        color="gray"
                                        size="sm"
                                        class="close-pane"
                                        :label="trans('filament-media::media.close')"
                                    />
                                </div>
                            </x-slot>

                            <div
                                class="table-responsive overflow-auto"
                                style="max-height: 180px"
                            >
                                <table class="w-full text-start divide-y divide-gray-200 dark:divide-white/5 fm-upload-progress-table">
                                    {{-- Content will be injected via JS --}}
                                </table>
                            </div>
                        </x-filament::section>
                    </div>
                </div>
            </div>


        <button class="hidden js-fm-clipboard-temp"></button>



        <div id="filament_media_loading" class="hidden" >
            <x-filament::loading-indicator class="h-12 w-12 text-primary loading-spinner loading-spinner--transparent" />
        </div>

        <div id="filament_action_item" class="hidden">
            <button
                class="block w-full px-4 py-2 text-start text-sm hover:bg-gray-50 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-800 transition duration-150 ease-in-out js-files-action"
                data-action="__action__"
            >
                <span class="me-2 opacity-50 dropdown-item-icon">__icon__</span>
                <span class="dropdown-item-label">__name__</span>
            </button>
        </div>

        <div id="filament_media_items_list" class="hidden">
            <div class="fm-media-list">
                <ul>
                    <li class="no-items">
                        <x-filament::icon icon="heroicon-m-cloud-arrow-up" />
                        <h3>Drop files and folders here</h3>
                        <p>Or use the upload button above.</p>
                    </li>
                    <li class="fm-media-list-title up-one-level js-up-one-level" title="{{ trans('filament-media::media.up_level') }}">
                        <div class="custom-checkbox"></div>

                        <div class="fm-media-file-size"></div>
                        <div class="fm-media-created-at"></div>
                    </li>
                </ul>
            </div>
        </div>

        <div id="filament_media_items_tiles" class="hidden">
            <div class="fm-media-grid">
                <ul>
                    <li class="no-items">
                        __noItemIcon__
                        <h3>__noItemTitle__</h3>
                        <p>__noItemMessage__</p>
                    </li>
                    <li class="fm-media-list-title up-one-level js-up-one-level">
                        <div class="fm-media-item" data-context="__type__" title="{{ trans('filament-media::media.up_level') }}">
                            <div class="fm-media-thumbnail">
                                <x-filament::icon icon="heroicon-m-arrow-turn-up-left"  />
                            </div>
                            <div class="fm-media-description">
                                <div class="title">...</div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <div id="filament_media_items_list_element" class="hidden">
            <li class="fm-media-list-title js-media-list-title js-context-menu" data-context="__type__" title="__name__" data-id="__id__" data-data='__data__' >
                <div class="custom-checkbox">
                    <label>
                        <input type="checkbox">
                        <span></span>
                    </label>
                </div>
                <div class="fm-media-file-name flex gap-2">
                    __thumb__
                    <span>__name__</span>
                </div>
                <div class="fm-media-file-size">__size__</div>
                <div class="fm-media-created-at">__date__</div>
            </li>
        </div>

        <div id="filament_media_items_tiles_element" class="hidden">
            <li class="fm-media-list-title js-media-list-title js-context-menu" data-context="__type__" data-id="__id__" data-data='__data__' >
                <input type="checkbox" class="hidden">
                <div class="fm-media-item" title="__name__">
                    <span class="media-item-selected">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <path d="M186.301 339.893L96 249.461l-32 30.507L186.301 402 448 140.506 416 110z"></path>
                        </svg>
                    </span>
                    <div class="fm-media-thumbnail">
                        __thumb__
                    </div>
                    <div class="fm-media-description">
                        <div class="title" data-file-id="{{ e(request()->input('file_id', '')) }}">__name__</div>
                    </div>
                </div>
            </li>
        </div>

        <div id="filament_media_upload_progress_item" class="hidden">
            <div class="flex flex-row gap-2 hover:bg-gray-50 dark:hover:bg-white/5 transition duration-75 justify-between">
                <div class="p-4 align-top">
                    <div class="flex flex-col gap-1 justify-between">
                        <span class="file-name font-medium text-gray-950 dark:text-white">__fileName__</span>
                        <div class="file-error text-sm text-danger-600 dark:text-danger-400"></div>
                    </div>
                </div>
                <div class="p-4 align-top">
                    <span class="file-size text-sm text-gray-500 dark:text-gray-400">__fileSize__</span>
                </div>
                <div class="p-4 align-top text-end">
                    <div class="flex flex-col items-end gap-1">
                        <span class="file-status text-sm font-medium text-__status__-600 dark:text-__status__-400">__message__</span>
                        <span class="progress-percent text-xs text-gray-500 dark:text-gray-400"></span>
                    </div>
                </div>
            </div>
        </div>

        <div id="filament_media_breadcrumb_item" class="hidden">
            <li>
                <a href="#" data-folder="__folderId__" class="no-underline hover:text-primary-500 flex items-center gap-2 js-change-folder">
                    __icon__
                    __name__
                </a>
            </li>
        </div>


        <div id="filament_media_alt_text_item" class="hidden">
            <div class="mb-3">
                <div class="flex w-full">
                    <div class="flex items-center px-3 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 rounded-l-lg border-r-0">
                        __icon__
                    </div>
                    <input class="block w-full border-gray-300 dark:border-gray-600 rounded-r-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm bg-transparent" placeholder="__placeholder__" value="__value__">
                </div>
            </div>
        </div>



        <div class="media-download-popup" style="display: none">
            <div class="p-4 mb-4 text-sm text-fg-success-strong rounded-base bg-success-soft" role="alert">
                {{ trans('filament-media::media.prepare_file_to_download') }}
            </div>
        </div>

        </div>

        @include('filament-media::config')
    </div>

</x-filament-panels::page>
