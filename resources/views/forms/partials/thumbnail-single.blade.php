{{-- Single file thumbnail card with drag & drop + progress overlay --}}
{{-- Requires: $firstFile, $urlService, $disabled, $overlayStyle ('hover' | 'remove'), $previewStyle, $aspectSquare --}}
<div class="group relative {{ $aspectSquare ? 'aspect-square' : '' }}" style="{{ $previewStyle }}">
    <div
        x-on:dragover.prevent="isDragging = true; $event.dataTransfer.dropEffect = 'copy'"
        x-on:dragleave.prevent="isDragging = false"
        x-on:drop.prevent="handleDrop($event.dataTransfer.files)"
        :class="isDragging ? 'border-primary-500 ring-2 ring-primary-500/30' : 'border-gray-200 dark:border-gray-700'"
        class="relative overflow-hidden rounded-lg border-2 bg-gray-100 dark:bg-gray-800 h-full transition-colors"
    >
        @if($firstFile->canGenerateThumbnails() && $urlService->fileExists($firstFile->url))
            <img
                src="{{ $firstFile->preview_url }}"
                alt="{{ $firstFile->name }}"
                class="w-full h-full object-contain cursor-pointer"
                x-on:click="previewUrl = '{{ $firstFile->preview_url }}'"
            />
        @else
            @php
                $icon = match($firstFile->type) {
                    'image' => 'heroicon-o-photo',
                    'video' => 'heroicon-o-film',
                    'audio' => 'heroicon-o-musical-note',
                    default => 'heroicon-o-document',
                };
            @endphp
            <div class="w-full h-full flex flex-col items-center justify-center gap-2">
                <x-filament::icon :icon="$icon" class="w-12 h-12 text-gray-400 dark:text-gray-500" />
                <span class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[160px] px-2">{{ $firstFile->name }}</span>
            </div>
        @endif

        {{-- Upload progress overlay --}}
        @include('filament-media::forms.partials.upload-progress-overlay')

        {{-- Overlay actions --}}
        @unless($disabled)
            @if($overlayStyle === 'hover')
                {{-- Full hover overlay with change + remove --}}
                <div x-show="!isUploading" class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                    <button
                        type="button"
                        x-on:click="showPicker = true"
                        class="p-2 rounded-full bg-white/20 text-white hover:bg-white/30 transition-colors"
                        title="{{ trans('filament-media::media.javascript.change_image') }}"
                    >
                        <x-filament::icon icon="heroicon-m-arrow-path" class="w-5 h-5" />
                    </button>
                    <button
                        type="button"
                        x-on:click="removeFile({{ $firstFile->id }})"
                        class="p-2 rounded-full bg-white/20 text-white hover:bg-red-500/80 transition-colors"
                        title="{{ trans('filament-media::media.remove_file') }}"
                    >
                        <x-filament::icon icon="heroicon-m-trash" class="w-5 h-5" />
                    </button>
                </div>
            @else
                {{-- Simple remove button (top-right corner) --}}
                <button
                    type="button"
                    x-show="!isUploading"
                    x-on:click="removeFile({{ $firstFile->id }})"
                    class="absolute top-2 end-2 p-1.5 rounded-full bg-black/50 text-white opacity-0 group-hover:opacity-100 hover:bg-red-500/80 transition-all"
                    title="{{ trans('filament-media::media.remove_file') }}"
                >
                    <x-filament::icon icon="heroicon-m-x-mark" class="w-4 h-4" />
                </button>
            @endif
        @endunless
    </div>
</div>
