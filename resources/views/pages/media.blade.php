<x-filament-panels::page>
    <div>
        <div id="media-content" wire:ignore>

            <div class="rv-media-container">
                <div class="rv-media-wrapper">
                    <input
                        type="checkbox"
                        id="media_details_collapse"
                        class="d-none fake-click-event"
                    >

                    <div
                        id="rv-media-aside"
                        @class(['d-md-none' => FilamentMedia::getConfig('sidebar_display') !== 'vertical'])
                        style="--bb-offcanvas-width: 85%"
                    >
                        <div class="header">
                            <h5 class="offcanvas-title">
                                {{ trans('filament-media::media.menu_name') }}
                            </h5>
                            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                        </div>

                        <div class="body p-0">
                            <div class="list-group-flush">
                                <div class="list-group-header">
                                    {{ trans('filament-media::media.filter') }}
                                </div>
                                <div class="list-group-item">
                                    :action="true"
                                    class="js-rv-media-change-filter"
                                    data-type="filter"
                                    data-value="everything"
                                >
                                    <x-filament::icon icon="heroicon-m-funnel" />
                                    {{ trans('filament-media::media.everything') }}
                                </div>

                                @if (array_key_exists('image', FilamentMedia::getConfig('mime_types', [])))
                                    <div class="list-group-item">
                                        :action="true"
                                        class="js-rv-media-change-filter"
                                        data-type="filter"
                                        data-value="video"
                                    >
                                        <x-filament::icon icon="heroicon-m-photo" />
                                        {{ trans('filament-media::media.image') }}
                                    </div>
                                @endif

                                @if (array_key_exists('video', FilamentMedia::getConfig('mime_types', [])))
                                    <div class="list-group-item">
                                        :action="true"
                                        class="js-rv-media-change-filter"
                                        data-type="filter"
                                        data-value="document"
                                    >
                                        <x-filament::icon icon="heroicon-m-film" />
                                        {{ trans('filament-media::media.video') }}
                                    </div>
                                @endif

                                <div class="list-group-item">
                                    :action="true"
                                    class="js-rv-media-change-filter"
                                    data-type="filter"
                                    data-value="image"
                                >
                                    <x-filament::icon icon="heroicon-m-document" />
                                    {{ trans('filament-media::media.document') }}
                                </div>
                            </div>

                            <div class="list-group-flush">
                                <div class="list-group-header">
                                    {{ trans('filament-media::media.view_in') }}
                                </div>
                                <div class="list-group-item">
                                    :action="true"
                                    class="js-rv-media-change-filter"
                                    data-type="view_in"
                                    data-value="all_media"
                                >
                                    <x-filament::icon icon="heroicon-m-globe-alt" />
                                    {{ trans('filament-media::media.all_media') }}
                                </div>

                                    <div class="list-group-item">
                                        :action="true"
                                        class="js-rv-media-change-filter"
                                        data-type="view_in"
                                        data-value="trash"
                                    >
                                        <x-filament::icon icon="heroicon-m-trash" />
                                        {{ trans('filament-media::media.trash') }}
                                    </div>

                                <div class="list-group-item">
                                    :action="true"
                                    class="js-rv-media-change-filter"
                                    data-type="view_in"
                                    data-value="recent"
                                >
                                    <x-filament::icon icon="heroicon-m-clock" />
                                    {{ trans('filament-media::media.recent') }}
                                </div>

                                <div class="list-group-item">
                                    :action="true"
                                    class="js-rv-media-change-filter"
                                    data-type="view_in"
                                    data-value="favorites"
                                >
                                    <x-filament::icon icon="heroicon-m-star" />
                                    {{ trans('filament-media::media.favorites') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rv-media-main-wrapper">
                        <div class="flex-column rv-media-header p-0">
                            <div class="w-100 rv-media-top-header flex-wrap gap-3 d-flex justify-content-between align-items-start border-bottom bg-body">
                                <div class="d-flex flex-wrap gap-3 p-2 justify-content-between w-100 w-md-auto rv-media-actions  rounded-xl shadow-sm align-items-center">
                                    <x-filament::icon-button
                                        class="d-flex d-md-none bg-white dark:bg-gray-900"
                                        icon="heroicon-m-bars-3"
                                        data-bs-toggle="offcanvas"
                                        href="#rv-media-aside"
                                        :label="trans('filament-media::media.menu_name')"
                                        color="gray"
                                        size="sm"
                                    />

                                    <div class="rv-media-actions__controls d-flex flex-wrap align-items-center gap-3 shadow-sm rounded-lg p-3">
                                        <x-filament::dropdown class="d-none d-md-block bg-white dark:bg-gray-900 rounded-lg">
                                            <x-slot:trigger>
                                                <x-filament::button
                                                    type="button"
                                                    icon="heroicon-m-arrow-up-tray"
                                                    color="primary"
                                                    size="lg"
                                                    class="bg-white dark:bg-gray-900"
                                                    :label="trans('filament-media::media.upload')"
                                                >
                                                    {{ trans('filament-media::media.upload') }}
                                                </x-filament::button>
                                            </x-slot:trigger>
                                            <x-filament::dropdown.list>
                                                <x-filament::dropdown.list.item
                                                    icon="heroicon-m-globe-alt"
                                                    class="js-dropzone-upload dropdown-item"
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
                                            class="bg-white dark:bg-gray-900"
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
                                            class="js-change-action bg-white dark:bg-gray-900"
                                            icon="heroicon-m-arrow-path"
                                            data-type="refresh"
                                            :label="trans('filament-media::media.refresh')"
                                            size="lg"
                                        >
                                            {{ trans('filament-media::media.refresh') }}
                                        </x-filament::button>

                                        @if (FilamentMedia::getConfig('sidebar_display') !== 'vertical')
                                            <x-filament::dropdown class="d-none d-md-block">
                                                <x-slot:trigger>
                                                    <x-filament::button
                                                        type="button"
                                                        color="primary"
                                                        icon="heroicon-m-funnel"
                                                        class="js-rv-media-change-filter-group js-filter-by-type bg-white dark:bg-gray-900"
                                                        :tooltip="trans('filament-media::media.filter')"
                                                        :label="trans('filament-media::media.filter')"
                                                        size="lg"
                                                    >
                                                    {{ trans('filament-media::media.filter') }}
                                                    </x-filament::button>
                                                </x-slot:trigger>

                                                <x-filament::dropdown.list>
                                                    <x-filament::dropdown.list.item
                                                        icon="heroicon-m-arrow-path"
                                                        class="js-rv-media-change-filter"
                                                        data-type="filter"
                                                        data-value="everything"
                                                    >
                                                        {{ trans('filament-media::media.everything') }}
                                                    </x-filament::dropdown.list.item>

                                                    @if (array_key_exists('image', FilamentMedia::getConfig('mime_types', [])))
                                                        <x-filament::dropdown.list.item
                                                            icon="heroicon-m-photo"
                                                            class="js-rv-media-change-filter"
                                                            data-type="filter"
                                                            data-value="image"
                                                        >
                                                            {{ trans('filament-media::media.image') }}
                                                        </x-filament::dropdown.list.item>
                                                    @endif

                                                    @if (array_key_exists('video', FilamentMedia::getConfig('mime_types', [])))
                                                        <x-filament::dropdown.list.item
                                                            icon="heroicon-m-film"
                                                            class="js-rv-media-change-filter"
                                                            data-type="filter"
                                                            data-value="video"
                                                        >
                                                            {{ trans('filament-media::media.video') }}
                                                        </x-filament::dropdown.list.item>
                                                    @endif

                                                    <x-filament::dropdown.list.item
                                                        icon="heroicon-m-document-text"
                                                        class="js-rv-media-change-filter"
                                                        data-type="filter"
                                                        data-value="document"
                                                    >
                                                        {{ trans('filament-media::media.document') }}
                                                    </x-filament::dropdown.list.item>
                                                </x-filament::dropdown.list>
                                            </x-filament::dropdown>

                                            <x-filament::dropdown class="d-none d-md-block">
                                                <x-slot:trigger>
                                                    <x-filament::button
                                                        type="button"
                                                        color="primary"
                                                        icon="heroicon-m-eye"
                                                        class="js-rv-media-change-filter-group js-filter-by-view-in bg-white dark:bg-gray-900"
                                                        :tooltip="trans('filament-media::media.view_in')"
                                                        :label="trans('filament-media::media.view_in')"
                                                        size="lg"
                                                    >
                                                        {{ trans('filament-media::media.view_in') }}
                                                    </x-filament::button>
                                                </x-slot:trigger>

                                                <x-filament::dropdown.list>
                                                    <x-filament::dropdown.list.item
                                                        icon="heroicon-m-globe-alt"
                                                        class="js-rv-media-change-filter"
                                                        data-type="view_in"
                                                        data-value="all_media"
                                                    >
                                                        {{ trans('filament-media::media.all_media') }}
                                                    </x-filament::dropdown.list.item>

                                                    <x-filament::dropdown.list.item
                                                        icon="heroicon-m-trash"
                                                        class="js-rv-media-change-filter"
                                                        data-type="view_in"
                                                        data-value="trash"
                                                    >
                                                        {{ trans('filament-media::media.trash') }}
                                                    </x-filament::dropdown.list.item>

                                                    <x-filament::dropdown.list.item
                                                        icon="heroicon-m-clock"
                                                        class="js-rv-media-change-filter"
                                                        data-type="view_in"
                                                        data-value="recent"
                                                    >
                                                        {{ trans('filament-media::media.recent') }}
                                                    </x-filament::dropdown.list.item>

                                                    <x-filament::dropdown.list.item
                                                        icon="heroicon-m-star"
                                                        class="js-rv-media-change-filter"
                                                        data-type="view_in"
                                                        data-value="favorites"
                                                    >
                                                        {{ trans('filament-media::media.favorites') }}
                                                    </x-filament::dropdown.list.item>
                                                </x-filament::dropdown.list>
                                            </x-filament::dropdown>
                                        @endif

                                            <x-filament::icon-button
                                                type="button"
                                                color="danger"
                                                class="d-none js-files-action bg-white dark:bg-gray-900"
                                                data-action="empty_trash"
                                                icon="heroicon-m-trash"
                                                :label="trans('filament-media::media.empty_trash')"
                                                :tooltip="trans('filament-media::media.empty_trash')"
                                                    size="lg"
                                            />
                                    </div>
                                    <div class="rv-media-search">
                                        <form
                                            class="input-search-wrapper d-flex align-items-center"
                                            action=""
                                            method="GET"
                                        >
                                            <x-filament::input
                                                type="search"
                                                name="search"
                                                class="w-140 bg-white dark:bg-gray-900 border border-end-0 rounded-end-0 border-start-0 border-transparent rounded-start-0 h-10"
                                                placeholder="{{ trans('filament-media::media.search_file_and_folder') }}"
                                            />
                                            <x-filament::button
                                                type="submit"
                                                color="primary"
                                                icon="heroicon-m-magnifying-glass"
                                                size="lg"
                                                class="bg-white dark:bg-gray-900 border border-start-0 border-transparent rounded-start-0 h-10"
                                            >
                                            </x-filament::button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="row w-100 p-2 border-bottom bg-white dark:bg-gray-900">
                                <div class="col p-2 d-flex align-items-center rv-media-breadcrumb">
                                    <ul class="breadcrumb">
                                        <li>
                                            <a href="#" data-folder="0" class="text-decoration-none js-change-folder d-flex align-items-center gap-2">
                                                <x-filament::icon icon="heroicon-m-photo" />
                                                All media
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-auto d-flex justify-content-between justify-content-md-end align-items-center rv-media-tools gap-2 flex-wrap">
                                    <div
                                        class="btn-list d-flex align-items-center gap-2"
                                        role="group"
                                    >
                                        <x-filament::dropdown>
                                            <x-slot:trigger>
                                                <x-filament::button
                                                    icon="heroicon-m-document-arrow-up"
                                                    size="lg"
                                                    class="px-4 py-2 shadow-sm bg-transparent"
                                                >
                                                    {{ trans('filament-media::media.sort') }}
                                                </x-filament::button>
                                            </x-slot:trigger>

                                            <x-filament::dropdown.list>
                                                @foreach ($this->sorts as $key => $item)
                                                <x-filament::dropdown.list.item
                                                    :label="$item['label']"
                                                    :icon="$item['icon']"
                                                    class="js-rv-media-change-filter"
                                                    data-type="sort_by"
                                                    :data-value="$key"
                                                >
                                                {{ $item['label'] }}
                                                </x-filament::dropdown.list.item>
                                                @endforeach
                                            </x-filament::dropdown.list>
                                        </x-filament::dropdown>

                                        <x-filament::dropdown class="rv-dropdown-actions hidden">
                                            <x-slot:trigger>
                                                <x-filament::button
                                                    icon="heroicon-m-hand-raised"
                                                    class="rv-dropdown-actions"
                                                    disabled
                                                    outlined
                                                >
                                                    {{ trans('filament-media::media.actions') }}
                                                </x-filament::button>
                                            </x-slot:trigger>

                                            <x-filament::dropdown.list class="rv-dropdown-actions-list" />
                                        </x-filament::dropdown>
                                    </div>
                                    <div
                                        class="btn-group js-rv-media-change-view-type ms-2 d-flex align-items-center gap-2"
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
                                        class="collapse-panel ms-2 d-none d-lg-flex shadow-sm"
                                        icon="heroicon-m-chevron-double-right"
                                        :label="trans('filament-media::media.details') ?? 'Toggle details'"
                                        size="lg"
                                    />
                                </div>
                            </div>
                        </div>

                        <main class="rv-media-main">
                            <div class="rv-media-items"></div>
                            <div class="rv-media-details" style="display: none">
                                <div class="rv-media-thumbnail">
                                    <x-filament::icon icon="heroicon-m-photo" />
                                </div>
                                <div class="rv-media-description">
                                    <div class="rv-media-name">
                                        <p>{{ trans('filament-media::media.nothing_is_selected') }}</p>
                                    </div>
                                </div>
                            </div>
                        </main>
                        <footer class="d-none rv-media-footer">
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
                    <div class="rv-upload-progress hide-the-pane position-fixed bottom-0 end-0 z-50 m-6 w-96 max-w-full">
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
                                <table class="w-full text-start divide-y divide-gray-200 dark:divide-white/5 rv-upload-progress-table">
                                    {{-- Content will be injected via JS --}}
                                </table>
                            </div>
                        </x-filament::section>
                    </div>
                </div>
            </div>


        <button class="d-none js-rv-clipboard-temp"></button>



        <div id="rv_media_loading" class="d-none" >
            <x-filament::loading-indicator class="h-12 w-12 text-primary loading-spinner loading-spinner--transparent" />
        </div>

        <div id="rv_action_item" class="d-none">
            <button
                class="dropdown-item js-files-action"
                data-action="__action__"
            >
                <span class="dropdown-item-icon">__icon__</span>
                <span class="dropdown-item-label">__name__</span>
            </button>
        </div>

        <div id="rv_media_items_list" class="d-none">
            <div class="rv-media-list">
                <ul>
                    <li class="no-items">
                        <x-filament::icon icon="heroicon-m-cloud-arrow-up" />
                        <h3>Drop files and folders here</h3>
                        <p>Or use the upload button above.</p>
                    </li>
                    <li class="rv-media-list-title up-one-level js-up-one-level" title="{{ trans('filament-media::media.up_level') }}">
                        <div class="custom-checkbox"></div>

                        <div class="rv-media-file-size"></div>
                        <div class="rv-media-created-at"></div>
                    </li>
                </ul>
            </div>
        </div>

        <div id="rv_media_items_tiles" class="d-none">
            <div class="rv-media-grid">
                <ul>
                    <li class="no-items">
                        __noItemIcon__
                        <h3>__noItemTitle__</h3>
                        <p>__noItemMessage__</p>
                    </li>
                    <li class="rv-media-list-title up-one-level js-up-one-level">
                        <div class="rv-media-item" data-context="__type__" title="{{ trans('filament-media::media.up_level') }}">
                            <div class="rv-media-thumbnail">
                                <x-filament::icon icon="heroicon-m-arrow-turn-up-left"  />
                            </div>
                            <div class="rv-media-description">
                                <div class="title">...</div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <div id="rv_media_items_list_element" class="d-none">
            <li class="rv-media-list-title js-media-list-title js-context-menu" data-context="__type__" title="__name__" data-id="__id__" data-data='__data__' >
                <div class="custom-checkbox">
                    <label>
                        <input type="checkbox">
                        <span></span>
                    </label>
                </div>
                <div class="rv-media-file-name flex gap-2">
                    __thumb__
                    <span>__name__</span>
                </div>
                <div class="rv-media-file-size">__size__</div>
                <div class="rv-media-created-at">__date__</div>
            </li>
        </div>

        <div id="rv_media_items_tiles_element" class="d-none">
            <li class="rv-media-list-title js-media-list-title js-context-menu" data-context="__type__" data-id="__id__" data-data='__data__' >
                <input type="checkbox" class="hidden">
                <div class="rv-media-item" title="__name__">
                    <span class="media-item-selected">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <path d="M186.301 339.893L96 249.461l-32 30.507L186.301 402 448 140.506 416 110z"></path>
                        </svg>
                    </span>
                    <div class="rv-media-thumbnail">
                        __thumb__
                    </div>
                    <div class="rv-media-description">
                        <div class="title title{{ (new Codenzia\FilamentMedia\Helpers\BaseHelper)->stringify(request()->input('file_id')) }}">__name__</div>
                    </div>
                </div>
            </li>
        </div>

        <div id="rv_media_upload_progress_item" class="d-none">
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

        <div id="rv_media_breadcrumb_item" class="d-none">
            <li>
                <a href="#" data-folder="__folderId__" class="text-decoration-none js-change-folder">
                    __icon__
                    __name__
                </a>
            </li>
        </div>


        <div id="rv_media_alt_text_item" class="d-none">
            <div class="mb-3">
                <div class="input-group">
                    <div class="input-group-text">
                        __icon__
                    </div>
                    <input class="form-control" placeholder="__placeholder__" value="__value__">
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
