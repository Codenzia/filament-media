{{-- Text links for Browse Media / Upload File --}}
{{-- Requires: $directUpload, $isMultiple, $inputRef (unique x-ref name) --}}
<div class="flex items-center gap-2">
    <button type="button" x-on:click="showPicker = true" class="text-sm text-primary-500 hover:text-primary-600 font-medium hover:underline cursor-pointer">
        {{ trans('filament-media::media.browse_media') }}
    </button>
    @if($directUpload)
        <span class="text-xs text-gray-400">{{ trans('filament-media::media.or') }}</span>
        <button type="button" x-on:click="$refs.{{ $inputRef }}.click()" class="text-sm text-primary-500 hover:text-primary-600 font-medium hover:underline cursor-pointer">
            {{ trans('filament-media::media.upload_file') }}
        </button>
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
    @endif
</div>
