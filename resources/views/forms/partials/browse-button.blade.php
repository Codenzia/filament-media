{{-- Browse / Upload Buttons (shared partial for dropdown and integratedDropdown styles) --}}
{{-- Requires: $directUpload, $isMultiple, $inputRef (unique x-ref name) --}}
{{-- Optional: $fullWidth (default false) — when true, button stretches to fill parent width --}}
@php $fullWidth = $fullWidth ?? false; @endphp
@if($directUpload)
    <div class="relative {{ $fullWidth ? 'w-full' : '' }}">
        <x-filament::dropdown placement="bottom-start" teleport :class="$fullWidth ? 'w-full' : ''">
            <x-slot name="trigger">
                <x-filament::button type="button" color="gray" icon="heroicon-m-photo" icon-position="before" :class="$fullWidth ? 'w-full justify-center' : ''">
                    {{ trans('filament-media::media.browse_media') }}
                    <x-filament::icon icon="heroicon-m-chevron-down" class="w-4 h-4 ms-1" />
                </x-filament::button>
            </x-slot>

            <x-filament::dropdown.list>
                <x-filament::dropdown.list.item icon="heroicon-m-photo" x-on:click="showPicker = true">
                    {{ trans('filament-media::media.browse_media') }}
                </x-filament::dropdown.list.item>

                <x-filament::dropdown.list.item icon="heroicon-m-arrow-up-tray" x-on:click="$refs.{{ $inputRef }}.click()">
                    {{ trans('filament-media::media.upload_file') }}
                </x-filament::dropdown.list.item>
            </x-filament::dropdown.list>
        </x-filament::dropdown>

        <input
            type="file"
            class="hidden"
            x-ref="{{ $inputRef }}"
            x-on:change="handleDirectUpload($event.target.files); $event.target.value = '';"
            {{ $isMultiple ? 'multiple' : '' }}
            @if(!empty($getAcceptedFileTypes()))
                accept="{{ implode(',', $getAcceptedFileTypes()) }}"
            @endif
        />
    </div>
@else
    <x-filament::button
        type="button"
        color="gray"
        icon="heroicon-m-photo"
        x-on:click="showPicker = true"
        :class="$fullWidth ? 'w-full justify-center' : ''"
    >
        {{ trans('filament-media::media.browse_media') }}
    </x-filament::button>
@endif
