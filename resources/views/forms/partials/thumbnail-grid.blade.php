{{-- Multiple files thumbnail grid with "add more" tile --}}
{{-- Requires: $fileIds, $files, $urlService, $disabled --}}
<div class="flex flex-wrap gap-3">
    @foreach($fileIds as $fileId)
        @php $file = $files->get($fileId); @endphp
        @if($file)
            <div class="group relative w-24">
                <div class="relative overflow-hidden rounded-lg border-2 border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 aspect-square">
                    @if($file->canGenerateThumbnails() && $urlService->fileExists($file->url))
                        <img
                            src="{{ $file->preview_url }}"
                            alt="{{ $file->name }}"
                            class="w-full h-full object-contain cursor-pointer"
                            x-on:click="previewUrl = '{{ $file->preview_url }}'"
                        />
                    @else
                        @php
                            $icon = match($file->type) {
                                'image' => 'heroicon-o-photo',
                                'video' => 'heroicon-o-film',
                                'audio' => 'heroicon-o-musical-note',
                                default => 'heroicon-o-document',
                            };
                        @endphp
                        <div class="w-full h-full flex items-center justify-center">
                            <x-filament::icon :icon="$icon" class="w-8 h-8 text-gray-400 dark:text-gray-500" />
                        </div>
                    @endif

                    {{-- Remove button overlay --}}
                    @unless($disabled)
                        <button
                            type="button"
                            x-on:click="removeFile({{ $file->id }})"
                            class="absolute top-1 end-1 p-1 rounded-full bg-black/50 text-white opacity-0 group-hover:opacity-100 hover:bg-red-500/80 transition-all"
                            title="{{ trans('filament-media::media.remove_file') }}"
                        >
                            <x-filament::icon icon="heroicon-m-x-mark" class="w-3.5 h-3.5" />
                        </button>
                    @endunless
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate text-center">{{ $file->name }}</p>
            </div>
        @endif
    @endforeach

    {{-- Add more button with drag-and-drop --}}
    @unless($disabled)
        <div class="w-24">
            <div
                x-on:click="showPicker = true"
                x-on:dragover.prevent="isDragging = true; $event.dataTransfer.dropEffect = 'copy'"
                x-on:dragleave.prevent="isDragging = false"
                x-on:drop.prevent="handleDrop($event.dataTransfer.files)"
                :class="isDragging ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50'"
                class="relative w-full aspect-square rounded-lg border-2 border-dashed hover:border-primary-400 dark:hover:border-primary-500 flex items-center justify-center transition-colors cursor-pointer"
            >
                {{-- Upload progress overlay --}}
                @include('filament-media::forms.partials.upload-progress-overlay')

                <x-filament::icon icon="heroicon-m-plus" x-show="!isDragging && !isUploading" class="w-6 h-6 text-gray-400 dark:text-gray-500" />
                <x-filament::icon icon="heroicon-o-cloud-arrow-up" x-show="isDragging && !isUploading" x-cloak class="w-6 h-6 text-primary-500" />
            </div>
        </div>
    @endunless
</div>
