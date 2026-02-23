{{-- Empty state placeholder with drag & drop + progress overlay --}}
{{-- Requires: $disabled, $directUpload, $isMultiple, $inputRef (unique x-ref name), $previewStyle, $aspectSquare --}}
{{-- Optional: $showActions (default true) — when false, shows just icon + "no file selected" text --}}
@php $showActions = $showActions ?? true; @endphp
@unless($disabled)
    <div
        x-on:dragover.prevent="isDragging = true; $event.dataTransfer.dropEffect = 'copy'"
        x-on:dragleave.prevent="isDragging = false"
        x-on:drop.prevent="handleDrop($event.dataTransfer.files)"
        :class="isDragging ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50'"
        style="{{ $previewStyle }}"
        class="relative {{ $aspectSquare ? 'aspect-square' : '' }} rounded-lg border-2 border-dashed hover:border-primary-400 dark:hover:border-primary-500 flex flex-col items-center justify-center gap-2 transition-colors"
    >
        {{-- Upload progress overlay --}}
        @include('filament-media::forms.partials.upload-progress-overlay')

        {{-- Drag-over state --}}
        <template x-if="isDragging && !isUploading">
            <div class="flex flex-col items-center gap-2">
                <x-filament::icon icon="heroicon-o-cloud-arrow-up" class="w-12 h-12 text-primary-500" />
                <span class="text-sm text-primary-500 font-medium">
                    {{ trans('filament-media::media.direct_upload_drop_short') }}
                </span>
            </div>
        </template>

        {{-- Default state --}}
        <template x-if="!isDragging && !isUploading">
            <div class="flex flex-col items-center gap-2">
                <x-filament::icon icon="heroicon-o-photo" class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                @if($showActions)
                    <div class="flex flex-col items-center gap-0.5">
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
                @else
                    <span class="text-sm text-gray-400 italic">{{ trans('filament-media::media.no_file_selected') }}</span>
                @endif
            </div>
        </template>
    </div>
@else
    <div style="{{ $previewStyle }}" class="{{ $aspectSquare ? 'aspect-square' : '' }} rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50 flex flex-col items-center justify-center gap-2">
        <x-filament::icon icon="heroicon-o-photo" class="w-12 h-12 text-gray-300 dark:text-gray-600" />
        <span class="text-sm text-gray-400 italic">{{ trans('filament-media::media.no_file_selected') }}</span>
    </div>
@endunless
