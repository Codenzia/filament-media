<x-filament-panels::page>
    <div>
        <div id="media-content" wire:ignore>

            <div class="rv-media-container">
                <x-core::card class="rv-media-wrapper">
                    <input
                        type="checkbox"
                        id="media_details_collapse"
                        class="d-none fake-click-event"
                    >

                    <x-core::offcanvas
                        id="rv-media-aside"
                        @class(['d-md-none' => FilamentMedia::getConfig('sidebar_display') !== 'vertical'])
                        style="--bb-offcanvas-width: 85%"
                    >
                        <x-core::offcanvas.header>
                            <x-core::offcanvas.title>
                                {{ trans('core/media::media.menu_name') }}
                            </x-core::offcanvas.title>
                            <x-core::offcanvas.close-button />
                        </x-core::offcanvas.header>

                        <x-core::offcanvas.body class="p-0">
                            <x-core::list-group :flush="true">
                                <x-core::list-group.header>
                                    {{ trans('core/media::media.filter') }}
                                </x-core::list-group.header>
                                <x-core::list-group.item
                                    :action="true"
                                    class="js-rv-media-change-filter"
                                    data-type="filter"
                                    data-value="everything"
                                >
                                    <x-filament::icon icon="heroicon-m-funnel" />
                                    {{ trans('core/media::media.everything') }}
                                </x-core::list-group.item>

                                @if (array_key_exists('image', FilamentMedia::getConfig('mime_types', [])))
                                    <x-core::list-group.item
                                        :action="true"
                                        class="js-rv-media-change-filter"
                                        data-type="filter"
                                        data-value="video"
                                    >
                                        <x-filament::icon icon="heroicon-m-photo" />
                                        {{ trans('core/media::media.image') }}
                                    </x-core::list-group.item>
                                @endif

                                @if (array_key_exists('video', FilamentMedia::getConfig('mime_types', [])))
                                    <x-core::list-group.item
                                        :action="true"
                                        class="js-rv-media-change-filter"
                                        data-type="filter"
                                        data-value="document"
                                    >
                                        <x-filament::icon icon="heroicon-m-film" />
                                        {{ trans('core/media::media.video') }}
                                    </x-core::list-group.item>
                                @endif

                                <x-core::list-group.item
                                    :action="true"
                                    class="js-rv-media-change-filter"
                                    data-type="filter"
                                    data-value="image"
                                >
                                    <x-filament::icon icon="heroicon-m-document" />
                                    {{ trans('core/media::media.document') }}
                                </x-core::list-group.item>
                            </x-core::list-group>

                            <x-core::list-group :flush="true">
                                <x-core::list-group.header>
                                    {{ trans('core/media::media.view_in') }}
                                </x-core::list-group.header>
                                <x-core::list-group.item
                                    :action="true"
                                    class="js-rv-media-change-filter"
                                    data-type="view_in"
                                    data-value="all_media"
                                >
                                    <x-filament::icon icon="heroicon-m-globe-alt" />
                                    {{ trans('core/media::media.all_media') }}
                                </x-core::list-group.item>

                                    <x-core::list-group.item
                                        :action="true"
                                        class="js-rv-media-change-filter"
                                        data-type="view_in"
                                        data-value="trash"
                                    >
                                        <x-filament::icon icon="heroicon-m-trash" />
                                        {{ trans('core/media::media.trash') }}
                                    </x-core::list-group.item>

                                <x-core::list-group.item
                                    :action="true"
                                    class="js-rv-media-change-filter"
                                    data-type="view_in"
                                    data-value="recent"
                                >
                                    <x-filament::icon icon="heroicon-m-clock" />
                                    {{ trans('core/media::media.recent') }}
                                </x-core::list-group.item>

                                <x-core::list-group.item
                                    :action="true"
                                    class="js-rv-media-change-filter"
                                    data-type="view_in"
                                    data-value="favorites"
                                >
                                    <x-filament::icon icon="heroicon-m-star" />
                                    {{ trans('core/media::media.favorites') }}
                                </x-core::list-group.item>
                            </x-core::list-group>
                        </x-core::offcanvas.body>
                    </x-core::offcanvas>

                    <div class="rv-media-main-wrapper">
                        <x-core::card.header class="flex-column rv-media-header p-0">
                            <div class="w-100 rv-media-top-header flex-wrap gap-3 d-flex justify-content-between align-items-start border-bottom bg-body">
                                <div class="d-flex flex-wrap gap-3 p-2 justify-content-between w-100 w-md-auto rv-media-actions  rounded-xl shadow-sm align-items-center">
                                    <x-filament::icon-button
                                        class="d-flex d-md-none bg-white dark:bg-gray-900"
                                        icon="heroicon-m-bars-3"
                                        data-bs-toggle="offcanvas"
                                        href="#rv-media-aside"
                                        :label="trans('core/media::media.menu_name')"
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
                                                    :label="trans('core/media::media.upload')"
                                                >
                                                    {{ trans('core/media::media.upload') }}
                                                </x-filament::button>
                                            </x-slot:trigger>
                                            <x-filament::dropdown.list>
                                                <x-filament::dropdown.list.item
                                                    icon="heroicon-m-globe-alt"
                                                    class="js-dropzone-upload dropdown-item"
                                                    data-type="view_in"
                                                    data-value="all_media"
                                                >
                                                    {{ trans('core/media::media.upload_from_local') }}
                                                </x-filament::dropdown.list.item>

                                                <x-filament::dropdown.list.item
                                                    icon="heroicon-m-trash"
                                                    wire:click="mountAction('download_url')"
                                                >
                                                    {{ trans('core/media::media.upload_from_url') }}
                                                </x-filament::dropdown.list.item>
                                            </x-filament::dropdown.list>

                                        </x-filament::dropdown>

                                        <x-filament::button
                                            type="button"
                                            color="primary"
                                            :tooltip="trans('core/media::media.create_folder')"
                                            class="bg-white dark:bg-gray-900"
                                            wire:click="mountAction('create_folder')"
                                            icon="heroicon-m-folder-plus"
                                            :label="trans('core/media::media.create_folder')"
                                            size="lg"
                                        >
                                            {{ trans('core/media::media.create_folder') }}
                                        </x-filament::button>

                                        <x-filament::button
                                            type="button"
                                            color="primary"
                                            :tooltip="trans('core/media::media.refresh')"
                                            class="js-change-action bg-white dark:bg-gray-900"
                                            icon="heroicon-m-arrow-path"
                                            data-type="refresh"
                                            :label="trans('core/media::media.refresh')"
                                            size="lg"
                                        >
                                            {{ trans('core/media::media.refresh') }}
                                        </x-filament::button>

                                        @if (FilamentMedia::getConfig('sidebar_display') !== 'vertical')
                                            <x-filament::dropdown class="d-none d-md-block">
                                                <x-slot:trigger>
                                                    <x-filament::button
                                                        type="button"
                                                        color="primary"
                                                        icon="heroicon-m-funnel"
                                                        class="js-rv-media-change-filter-group js-filter-by-type bg-white dark:bg-gray-900"
                                                        :tooltip="trans('core/media::media.filter')"
                                                        :label="trans('core/media::media.filter')"
                                                        size="lg"
                                                    >
                                                    {{ trans('core/media::media.filter') }}
                                                    </x-filament::button>
                                                </x-slot:trigger>

                                                <x-filament::dropdown.list>
                                                    <x-filament::dropdown.list.item
                                                        icon="heroicon-m-arrow-path"
                                                        class="js-rv-media-change-filter"
                                                        data-type="filter"
                                                        data-value="everything"
                                                    >
                                                        {{ trans('core/media::media.everything') }}
                                                    </x-filament::dropdown.list.item>

                                                    @if (array_key_exists('image', FilamentMedia::getConfig('mime_types', [])))
                                                        <x-filament::dropdown.list.item
                                                            icon="heroicon-m-photo"
                                                            class="js-rv-media-change-filter"
                                                            data-type="filter"
                                                            data-value="image"
                                                        >
                                                            {{ trans('core/media::media.image') }}
                                                        </x-filament::dropdown.list.item>
                                                    @endif

                                                    @if (array_key_exists('video', FilamentMedia::getConfig('mime_types', [])))
                                                        <x-filament::dropdown.list.item
                                                            icon="heroicon-m-film"
                                                            class="js-rv-media-change-filter"
                                                            data-type="filter"
                                                            data-value="video"
                                                        >
                                                            {{ trans('core/media::media.video') }}
                                                        </x-filament::dropdown.list.item>
                                                    @endif

                                                    <x-filament::dropdown.list.item
                                                        icon="heroicon-m-document-text"
                                                        class="js-rv-media-change-filter"
                                                        data-type="filter"
                                                        data-value="document"
                                                    >
                                                        {{ trans('core/media::media.document') }}
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
                                                        :tooltip="trans('core/media::media.view_in')"
                                                        :label="trans('core/media::media.view_in')"
                                                        size="lg"
                                                    >
                                                        {{ trans('core/media::media.view_in') }}
                                                    </x-filament::button>
                                                </x-slot:trigger>

                                                <x-filament::dropdown.list>
                                                    <x-filament::dropdown.list.item
                                                        icon="heroicon-m-globe-alt"
                                                        class="js-rv-media-change-filter"
                                                        data-type="view_in"
                                                        data-value="all_media"
                                                    >
                                                        {{ trans('core/media::media.all_media') }}
                                                    </x-filament::dropdown.list.item>

                                                    <x-filament::dropdown.list.item
                                                        icon="heroicon-m-trash"
                                                        class="js-rv-media-change-filter"
                                                        data-type="view_in"
                                                        data-value="trash"
                                                    >
                                                        {{ trans('core/media::media.trash') }}
                                                    </x-filament::dropdown.list.item>

                                                    <x-filament::dropdown.list.item
                                                        icon="heroicon-m-clock"
                                                        class="js-rv-media-change-filter"
                                                        data-type="view_in"
                                                        data-value="recent"
                                                    >
                                                        {{ trans('core/media::media.recent') }}
                                                    </x-filament::dropdown.list.item>

                                                    <x-filament::dropdown.list.item
                                                        icon="heroicon-m-star"
                                                        class="js-rv-media-change-filter"
                                                        data-type="view_in"
                                                        data-value="favorites"
                                                    >
                                                        {{ trans('core/media::media.favorites') }}
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
                                                :label="trans('core/media::media.empty_trash')"
                                                :tooltip="trans('core/media::media.empty_trash')"
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
                                                class="w-100 bg-white dark:bg-gray-900 border border-end-0 rounded-end-0"
                                                placeholder="{{ trans('core/media::media.search_file_and_folder') }}"
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
                                <div class="col-md-auto d-flex justify-content-between justify-content-md-end align-items-center rv-media-tools">
                                    <div
                                        class="btn-list"
                                        role="group"
                                    >
                                        <x-filament::dropdown>
                                            <x-slot:trigger>
                                                <x-filament::button
                                                    icon="heroicon-m-document-arrow-up"
                                                    outlined
                                                >
                                                    {{ trans('core/media::media.sort') }}
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

                                        <x-filament::dropdown class="rv-dropdown-actions">
                                            <x-slot:trigger>
                                                <x-filament::button
                                                    icon="heroicon-m-hand-raised"
                                                    class="rv-dropdown-actions"
                                                    disabled
                                                    outlined
                                                >
                                                    {{ trans('core/media::media.actions') }}
                                                </x-filament::button>
                                            </x-slot:trigger>

                                            <x-filament::dropdown.list class="rv-dropdown-actions-list" />
                                        </x-filament::dropdown>
                                    </div>
                                    <div
                                        class="btn-group js-rv-media-change-view-type ms-2"
                                        role="group"
                                    >
                                        <x-filament::icon-button
                                            type="button"
                                            data-type="tiles"
                                            icon="heroicon-m-squares-2x2"
                                            :label="trans('core/media::media.view_type') ?? 'Tiles'"
                                        />
                                        <x-filament::icon-button
                                            type="button"
                                            data-type="list"
                                            icon="heroicon-m-list-bullet"
                                            :label="trans('core/media::media.view_type') ?? 'List'"
                                        />
                                    </div>
                                    <x-filament::icon-button
                                        tag="label"
                                        for="media_details_collapse"
                                        class="collapse-panel ms-2 d-none d-lg-flex"
                                        icon="heroicon-m-chevron-double-right"
                                        :label="trans('core/media::media.details') ?? 'Toggle details'"
                                    />
                                </div>
                            </div>
                        </x-core::card.header>

                        <main class="rv-media-main">
                            <div class="rv-media-items"></div>
                            <div class="rv-media-details" style="display: none">
                                <div class="rv-media-thumbnail">
                                    <x-filament::icon icon="heroicon-m-photo" />
                                </div>
                                <div class="rv-media-description">
                                    <div class="rv-media-name">
                                        <p>{{ trans('core/media::media.nothing_is_selected') }}</p>
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
                                :label="trans('core/media::media.insert')"
                                :tooltip="trans('core/media::media.insert')"
                            />
                        </footer>
                    </div>
                    <div class="rv-upload-progress hide-the-pane position-fixed bottom-0 end-0 ">
                        <x-core::card>
                            <x-core::card.header class="position-relative">
                                <h3 class="panel-title mb-0">{{ trans('core/media::media.upload_progress') }}</h3>
                                <x-filament::icon-button
                                    class="close-pane position-absolute top-50 bg-primary text-white text-center p-0"
                                    color="primary"
                                    icon="heroicon-m-x-mark"
                                    :label="trans('core/media::media.close')"
                                />
                            </x-core::card.header>
                            <div
                                class="table-responsive overflow-auto"
                                style="max-height: 180px"
                            >
                                <x-core::table>
                                    <x-core::table.body class="rv-upload-progress-table"></x-core::table.body>
                                </x-core::table>
                            </div>
                        </x-core::card>
                    </div>
                </x-core::card>
            </div>




            <x-core::modal
                id="modal_rename_items"
                :title="trans('core/media::media.rename')"
                :has-form="true"
                :form-attrs="['class' => 'form-rename']"
            >
                <div class="rename-items"></div>
                <div class="modal-notice"></div>

                <x-slot:footer>
                    <x-filament::icon-button
                        data-bs-dismiss="modal"
                        icon="heroicon-m-x-mark"
                        :label="trans('core/media::media.close')"
                        :tooltip="trans('core/media::media.close')"
                    />
                    <x-filament::icon-button
                        type="submit"
                        color="primary"
                        icon="heroicon-m-check"
                        :label="trans('core/media::media.save_changes')"
                        :tooltip="trans('core/media::media.save_changes')"
                    />
                </x-slot:footer>
            </x-core::modal>

            <x-core::modal
                id="modal_alt_text_items"
                :title="trans('core/media::media.alt_text')"
                :has-form="true"
                :form-attrs="['class' => 'form-alt-text']"
            >
                <div class="alt-text-items"></div>
                <div class="modal-notice"></div>

                <x-slot:footer>
                    <x-filament::icon-button
                        data-bs-dismiss="modal"
                        icon="heroicon-m-x-mark"
                        :label="trans('core/media::media.close')"
                        :tooltip="trans('core/media::media.close')"
                    />
                    <x-filament::icon-button
                        type="submit"
                        color="primary"
                        icon="heroicon-m-check"
                        :label="trans('core/media::media.save_changes')"
                        :tooltip="trans('core/media::media.save_changes')"
                    />
                </x-slot:footer>
            </x-core::modal>

            <x-core::modal
                id="modal_trash_items"
                :title="trans('core/media::media.move_to_trash')"
                :has-form="true"
                :form-attrs="['class' => 'form-delete-items']"
            >
                <p>{{ trans('core/media::media.confirm_trash') }}</p>

                <x-core::form.checkbox
                    :label="trans('core/media::media.skip_trash')"
                    :helper_text="trans('core/media::media.skip_trash_description')"
                    name="skip_trash"
                    :checked="false"
                    id="skip_trash"
                />

                <div class="modal-notice"></div>

                <x-slot:footer>
                    <button
                        type="submit"
                        class="btn btn-danger"
                    >{{ trans('core/media::media.confirm') }}</button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        data-bs-dismiss="modal"
                    >{{ trans('core/media::media.close') }}</button>
                </x-slot:footer>
            </x-core::modal>

            <x-core::modal
                id="modal_delete_items"
                :title="trans('core/media::media.confirm_delete')"
                :has-form="true"
                :form-attrs="['class' => 'form-delete-items']"
            >
                <p>{{ trans('core/media::media.confirm_delete_description') }}</p>
                <div class="modal-notice"></div>

                <x-slot:footer>
                    <button
                        type="submit"
                        class="btn btn-danger"
                    >{{ trans('core/media::media.confirm') }}</button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        data-bs-dismiss="modal"
                    >{{ trans('core/media::media.close') }}</button>
                </x-slot:footer>
            </x-core::modal>

            <x-core::modal
                id="modal_empty_trash"
                :title="trans('core/media::media.empty_trash_title')"
                :has-form="true"
                :form-attrs="['class' => 'form-empty-trash']"
            >
                <p>{{ trans('core/media::media.empty_trash_description') }}</p>
                <div class="modal-notice"></div>

                <x-slot:footer>
                    <button
                        type="submit"
                        class="btn btn-danger"
                    >{{ trans('core/media::media.confirm') }}</button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        data-bs-dismiss="modal"
                    >{{ trans('core/media::media.close') }}</button>
                </x-slot:footer>
            </x-core::modal>





        <x-core::modal
        title="{{ trans('core/media::media.crop') }}"
        id="modal_crop_image"
        size="lg"
        :form-attrs="['class' => 'rv-form form-crop']"
        :has-form="true"
        >
        <div>
            <input
                type="hidden"
                name="image_id"
            >
            <input
                type="hidden"
                name="crop_data"
            >
            <div class="row">
                <div class="col-lg-9">
                    <div class="crop-image"></div>
                </div>
                <div class="col-lg-3">
                    <div class="mt-3">
                        <x-core::form.text-input
                            label="{{ trans('core/media::media.cropper.height') }}"
                            name="dataHeight"
                            id="dataHeight"
                        />

                        <x-core::form.text-input
                            label="{{ trans('core/media::media.cropper.width') }}"
                            name="dataWidth"
                            id="dataWidth"
                        />

                        <x-core::form.checkbox
                            :label="trans('core/media::media.cropper.aspect_ratio')"
                            name="aspectRatio"
                            :checked="false"
                            id="aspectRatio"
                        />
                    </div>
                </div>
            </div>
        </div>
        <x-slot:footer>
            <x-filament::icon-button
                data-bs-dismiss="modal"
                icon="heroicon-m-x-mark"
                :label="trans('core/media::media.close')"
                :tooltip="trans('core/media::media.close')"
            />

            <x-filament::icon-button
                type="submit"
                color="primary"
                icon="heroicon-m-scissors"
                :label="trans('core/media::media.crop')"
                :tooltip="trans('core/media::media.crop')"
            />
        </x-slot:footer>
        </x-core::modal>


        <x-core::modal
        title="{{ trans('core/media::media.crop') }}"
        id="modal_crop_image"
        size="lg"
        :form-attrs="['class' => 'rv-form form-crop']"
        :has-form="true"
        >
        <div>
            <input
                type="hidden"
                name="image_id"
            >
            <input
                type="hidden"
                name="crop_data"
            >
            <div class="row">
                <div class="col-lg-9">
                    <div class="crop-image"></div>
                </div>
                <div class="col-lg-3">
                    <div class="mt-3">
                        <x-core::form.text-input
                            label="{{ trans('core/media::media.cropper.height') }}"
                            name="dataHeight"
                            id="dataHeight"
                        />

                        <x-core::form.text-input
                            label="{{ trans('core/media::media.cropper.width') }}"
                            name="dataWidth"
                            id="dataWidth"
                        />

                        <x-core::form.checkbox
                            :label="trans('core/media::media.cropper.aspect_ratio')"
                            name="aspectRatio"
                            :checked="false"
                            id="aspectRatio"
                        />
                    </div>
                </div>
            </div>
        </div>
        <x-slot:footer>
            <x-filament::icon-button
                data-bs-dismiss="modal"
                icon="heroicon-m-x-mark"
                :label="trans('core/media::media.close')"
                :tooltip="trans('core/media::media.close')"
            />

            <x-filament::icon-button
                type="submit"
                color="primary"
                icon="heroicon-m-scissors"
                :label="trans('core/media::media.crop')"
                :tooltip="trans('core/media::media.crop')"
            />
        </x-slot:footer>
        </x-core::modal>



        <x-core::modal
        id="modal-properties"
        :title="trans('core/media::media.properties.name')"
        >
        <input type="hidden" name="selected">

        <x-core::form.color-selector
            :label="trans('core/media::media.properties.color_label')"
            name="color"
            :choices="FilamentMedia::getFolderColors()"
        />

        <x-slot:footer>
            <x-filament::icon-button
                data-bs-dismiss="modal"
                icon="heroicon-m-x-mark"
                :label="trans('core/media::media.close')"
                :tooltip="trans('core/media::media.close')"
            />

            <x-filament::icon-button
                type="submit"
                color="primary"
                icon="heroicon-m-check"
                :label="trans('core/media::media.save_changes')"
                :tooltip="trans('core/media::media.save_changes')"
            />
        </x-slot:footer>
        </x-core::modal>



        <x-core::modal
        id="modal_share_items"
        :title="trans('core/media::media.share')"
        >
        <div class="share-items">
            <div class="mb-3">
                <label class="form-label" for="media-share-type">
                    {{ trans('core/media::media.share_type') }}
                </label>
                <select
                    name="share_type"
                    id="media-share-type"
                    class="form-select"
                    data-bb-value="share-type"
                >
                    <option value="url">{{ trans('core/media::media.share_as_url') }}</option>
                    <option value="indirect_url">{{ trans('core/media::media.share_as_indirect_url') }}</option>
                    <option value="html">{{ trans('core/media::media.share_as_html') }}</option>
                    <option value="markdown">{{ trans('core/media::media.share_as_markdown') }}</option>
                </select>
            </div>

            <div class="mb-3" data-bb-value="results">
                <label class="form-label" for="media-share-results">
                    {{ trans('core/media::media.share_results') }}
                </label>
                <textarea
                    id="media-share-results"
                    class="form-control"
                    rows="3"
                    readonly
                    data-bb-value="share-result"
                ></textarea>
            </div>

            <div class="mb-0 text-end">
                <x-filament::icon-button
                    class="btn-icon"
                    data-bb-toggle="clipboard"
                    data-clipboard-parent="#modal_share_items .share-items"
                    data-clipboard-target="[data-bb-value='share-result']"
                    icon="heroicon-m-clipboard"
                    :label="trans('core/media::media.copy') ?? 'Copy'"
                />
            </div>
        </div>
        </x-core::modal>

        <button class="d-none js-rv-clipboard-temp"></button>



        <div id="rv_media_loading" class="d-none">
            {{-- <x-filament::loading-indicator class="h-5 w-5 text-primary" /> --}}
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
                    <li class="rv-media-list-title up-one-level js-up-one-level" title="{{ trans('core/media::media.up_level') }}">
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
                        <div class="rv-media-item" data-context="__type__" title="{{ trans('core/media::media.up_level') }}">
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
            <li class="rv-media-list-title js-media-list-title js-context-menu" data-context="__type__" title="__name__" data-id="__id__">
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
            <li class="rv-media-list-title js-media-list-title js-context-menu" data-context="__type__" data-id="__id__">
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
            <x-core::table.body.row>
                <x-core::table.body.cell>
                    <span class="file-name">__fileName__</span>
                    <div class="file-error"></div>
                </x-core::table.body.cell>
                <x-core::table.body.cell>
                    <span class="file-size">__fileSize__</span>
                </x-core::table.body.cell>
                <x-core::table.body.cell>
                    <span class="file-status text-__status__">__message__</span>
                    <span class="progress-percent"></span>
                </x-core::table.body.cell>
            </x-core::table.body.row>
        </div>

        <div id="rv_media_breadcrumb_item" class="d-none">
            <li>
                <a href="#" data-folder="__folderId__" class="text-decoration-none js-change-folder">
                    __icon__
                    __name__
                </a>
            </li>
        </div>

        <div id="rv_media_rename_item" class="d-none">
            <div class="mb-3">
                <div class="input-group">
                    <div class="input-group-text">__icon__</div>
                    <input class="form-control" placeholder="__placeholder__" value="__value__">
                </div>
            </div>

            <x-core::form.checkbox
                data-folder-label="{{ trans('core/media::media.rename_physical_folder') }}"
                data-file-label="{{ trans('core/media::media.rename_physical_file') }}"
                label="__label__"
                name="rename_physical_file"
                data-bb-toggle="collapse"
                data-bb-target=".rename-physical-file-warning"
            />

            <div class="p-4 mb-4 text-sm text-fg-warning-strong rounded-base bg-warning-soft" role="alert" class="rename-physical-file-warning" style="display: none">
                {{ trans('core/media::media.rename_physical_file_warning') }}
            </div>
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
                {{ trans('core/media::media.prepare_file_to_download') }}
            </div>
        </div>

        </div>

        @include('filament-media::config')
    </div>

</x-filament-panels::page>
