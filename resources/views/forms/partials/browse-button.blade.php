{{-- Browse / Upload Buttons (shared partial for compact and integrated styles) --}}
@if($directUpload)
    <x-filament::dropdown placement="bottom-start" teleport>
        <x-slot name="trigger">
            <x-filament::button type="button" color="gray" icon="heroicon-m-photo" icon-position="before">
                {{ trans('filament-media::media.browse_media') }}
                <x-filament::icon icon="heroicon-m-chevron-down" class="w-4 h-4 ms-1" />
            </x-filament::button>
        </x-slot>

        <x-filament::dropdown.list>
            <x-filament::dropdown.list.item icon="heroicon-m-photo" x-on:click="showPicker = true">
                {{ trans('filament-media::media.browse_media') }}
            </x-filament::dropdown.list.item>

            <x-filament::dropdown.list.item icon="heroicon-m-arrow-up-tray" x-on:click="showUploader = true; uploadError = null;">
                {{ trans('filament-media::media.upload_file') }}
            </x-filament::dropdown.list.item>
        </x-filament::dropdown.list>
    </x-filament::dropdown>
@else
    <x-filament::button
        type="button"
        color="gray"
        icon="heroicon-m-photo"
        x-on:click="showPicker = true"
    >
        {{ trans('filament-media::media.browse_media') }}
    </x-filament::button>
@endif
