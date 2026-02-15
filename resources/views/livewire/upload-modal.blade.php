@php
    use Codenzia\FilamentMedia\Facades\FilamentMedia;
@endphp

<div>
    {{-- Upload Modal --}}
    <div
        x-data="filamentMediaUploader({
            uploadUrl: @js($this->getUploadUrl()),
            folderId: @entangle('folderId'),
            maxSize: {{ $this->getMaxSize() }},
            allowedTypes: @js(FilamentMedia::getAllowedMimeTypesString())
        })"
        x-modelable="open"
        x-model="$wire.isOpen"
        x-show="open"
        x-cloak
        x-on:upload-dropped-files.window="
            $wire.open($event.detail.folderId).then(() => {
                handleFiles($event.detail.files);
            });
        "
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="upload-modal-title"
        role="dialog"
        aria-modal="true"
    >
        {{-- Backdrop --}}
        <div
            x-show="open"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-900/50 dark:bg-gray-950/75"
            x-on:click="$wire.close()"
        ></div>

        {{-- Modal Content --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="open"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                class="relative w-full max-w-2xl transform overflow-hidden rounded-xl bg-white dark:bg-gray-900 shadow-xl transition-all border border-gray-200 dark:border-gray-700"
                x-on:click.stop
            >
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                    <h2 id="upload-modal-title" class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ trans('filament-media::media.upload_files') }}
                    </h2>
                    <button
                        type="button"
                        class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                        x-on:click="$wire.close()"
                    >
                        <x-filament::icon icon="heroicon-m-x-mark" class="w-5 h-5" />
                    </button>
                </div>

                {{-- Body --}}
                <div class="p-6">
                    {{-- Drag and Drop Zone --}}
                    <div
                        x-on:dragover.prevent="isDragging = true"
                        x-on:dragleave.prevent="isDragging = false"
                        x-on:drop.prevent="handleDrop($event)"
                        class="relative border-2 border-dashed rounded-xl p-8 text-center transition-colors"
                        :class="isDragging ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'"
                    >
                        <input
                            type="file"
                            multiple
                            x-on:change="handleFileSelect($event)"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                            accept="{{ FilamentMedia::getAllowedMimeTypesString() }}"
                        />

                        <div class="pointer-events-none">
                            <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                <x-filament::icon icon="heroicon-o-cloud-arrow-up" class="w-6 h-6 text-gray-400" />
                            </div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                {{ trans('filament-media::media.drop_files_to_upload') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ trans('filament-media::media.or_click_to_browse') }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                                {{ trans('filament-media::media.max_file_size', ['size' => $this->formatSize(FilamentMedia::getMaxSize())]) }}
                            </p>
                        </div>
                    </div>

                    {{-- Upload Queue --}}
                    @if(count($uploadQueue) > 0)
                        <div class="mt-6 space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ trans('filament-media::media.uploading_files') }}
                                </h3>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $completedCount }}/{{ count($uploadQueue) }} {{ trans('filament-media::media.completed') }}
                                </span>
                            </div>

                            <div class="max-h-64 overflow-y-auto space-y-2">
                                @foreach($uploadQueue as $key => $item)
                                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg" wire:key="upload-{{ $key }}">
                                        {{-- File Icon --}}
                                        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-white dark:bg-gray-700 flex items-center justify-center">
                                            @if($item['status'] === 'completed')
                                                <x-filament::icon icon="heroicon-m-check-circle" class="w-5 h-5 text-green-500" />
                                            @elseif($item['status'] === 'failed')
                                                <x-filament::icon icon="heroicon-m-x-circle" class="w-5 h-5 text-red-500" />
                                            @elseif($item['status'] === 'uploading')
                                                <x-filament::loading-indicator class="w-5 h-5" />
                                            @else
                                                <x-filament::icon icon="heroicon-o-document" class="w-5 h-5 text-gray-400" />
                                            @endif
                                        </div>

                                        {{-- File Info --}}
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                {{ $item['name'] }}
                                            </p>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $this->formatSize($item['size']) }}
                                                </span>
                                                @if($item['status'] === 'uploading')
                                                    <span class="text-xs text-primary-500">{{ $item['progress'] }}%</span>
                                                    <div class="flex-1 h-1 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                                        <div
                                                            class="h-full bg-primary-500 transition-all duration-300"
                                                            style="width: {{ $item['progress'] }}%"
                                                        ></div>
                                                    </div>
                                                @elseif($item['status'] === 'failed' && $item['error'])
                                                    <span class="text-xs text-red-500">{{ $item['error'] }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Actions --}}
                                        <div class="flex-shrink-0">
                                            @if($item['status'] !== 'uploading')
                                                <x-filament::icon-button
                                                    icon="heroicon-m-x-mark"
                                                    size="sm"
                                                    color="gray"
                                                    wire:click="removeFromQueue('{{ $key }}')"
                                                    :tooltip="trans('filament-media::media.remove')"
                                                />
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                    @if($failedCount > 0)
                        <span class="text-sm text-red-500 mr-auto">
                            {{ trans('filament-media::media.upload_failed_count', ['count' => $failedCount]) }}
                        </span>
                    @endif

                    <x-filament::button
                        color="gray"
                        wire:click="close"
                    >
                        {{ trans('filament-media::media.close') }}
                    </x-filament::button>
                </div>
            </div>
        </div>
    </div>
</div>

