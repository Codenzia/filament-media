{{-- Chip-style file list with thumbnail/icon, name, and remove button --}}
{{-- Requires: $chipDim (array from getChipDimensions()) --}}
{{-- Optional: $stretchItems (default false) — when true, chips stretch to fill parent width --}}
@php $stretchItems = $stretchItems ?? false; @endphp
@if(!empty($fileIds))
    <div class="flex flex-wrap gap-2 {{ $stretchItems ? '[&>div]:w-full' : '' }}">
        @foreach($fileIds as $fileId)
            @php $file = $files->get($fileId); @endphp
            @if($file)
                <div class="group relative flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    {{-- Thumbnail or Icon (clickable for preview) --}}
                    @if($file->canGenerateThumbnails() && $urlService->fileExists($file->url))
                        <button type="button" x-on:click="previewUrl = '{{ $file->preview_url }}'" class="cursor-pointer shrink-0">
                            <img
                                src="{{ $file->preview_url }}"
                                alt="{{ $file->name }}"
                                style="max-width: {{ $chipDim['thumb'] }}; max-height: {{ $chipDim['thumb'] }}"
                                class="rounded object-contain hover:ring-2 hover:ring-primary-500 transition-shadow"
                            />
                        </button>
                    @else
                        @php
                            $icon = match($file->type) {
                                'image' => 'heroicon-o-photo',
                                'video' => 'heroicon-o-film',
                                'audio' => 'heroicon-o-musical-note',
                                default => 'heroicon-o-document',
                            };
                        @endphp
                        <div
                            style="width: {{ $chipDim['thumb'] }}; height: {{ $chipDim['thumb'] }}"
                            class="rounded bg-gray-200 dark:bg-gray-700 flex items-center justify-center shrink-0"
                        >
                            <x-filament::icon
                                :icon="$icon"
                                style="width: {{ $chipDim['icon'] }}; height: {{ $chipDim['icon'] }}"
                                class="text-gray-900 dark:text-gray-400"
                            />
                        </div>
                    @endif

                    <span
                        style="font-size: {{ $chipDim['fontSize'] }}; max-width: {{ $chipDim['maxName'] }}"
                        class="text-gray-700 dark:text-gray-300 truncate"
                    >
                        {{ $file->name }}
                    </span>

                    {{-- Remove Button --}}
                    @unless($disabled)
                        <button
                            type="button"
                            x-on:click="removeFile({{ $file->id }})"
                            class="ml-1 p-0.5 rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                            title="{{ trans('filament-media::media.remove_file') }}"
                        >
                            <x-filament::icon icon="heroicon-m-x-mark" class="w-4 h-4" />
                        </button>
                    @endunless
                </div>
            @endif
        @endforeach
    </div>
@else
    <p class="text-sm text-gray-400 italic" x-show="!showUploader">{{ trans('filament-media::media.no_file_selected') }}</p>
@endif
