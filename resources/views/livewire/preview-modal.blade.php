@php
    use Codenzia\FilamentMedia\Facades\FilamentMedia;
@endphp

<div>
    {{-- Preview Modal --}}
    <div
        x-data="{
            open: @entangle('isOpen'),
            handleKeydown(e) {
                if (!this.open) return;

                if (e.key === 'Escape') {
                    $wire.close();
                } else if (e.key === 'ArrowRight') {
                    $wire.next();
                } else if (e.key === 'ArrowLeft') {
                    $wire.previous();
                }
            }
        }"
        x-show="open"
        x-cloak
        x-on:keydown.window="handleKeydown($event)"
        class="fm-preview-modal fixed inset-0 overflow-hidden"
        aria-labelledby="preview-modal-title"
        role="dialog"
        aria-modal="true"
    >
        {{-- Backdrop --}}
        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/90"
            x-on:click="$wire.close()"
        ></div>

        {{-- Modal Content --}}
        <div class="relative w-full h-full flex flex-col">
            {{-- Header --}}
            <header class="flex-shrink-0 flex items-center justify-between px-4 py-3 bg-black/50 z-10">
                <div class="flex items-center gap-3">
                    <h2 id="preview-modal-title" class="text-sm font-medium text-white truncate max-w-xs sm:max-w-md">
                        {{ $name }}
                    </h2>
                    <span class="text-xs text-gray-400">
                        {{ $size }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    @if($fileExists)
                        {{-- Download --}}
                        <a
                            href="{{ $fullUrl }}"
                            download
                            class="p-2 rounded-lg text-gray-300 hover:text-white hover:bg-white/10 transition-colors"
                            title="{{ trans('filament-media::media.download') }}"
                        >
                            <x-filament::icon icon="heroicon-m-arrow-down-tray" class="w-5 h-5" />
                        </a>

                        {{-- Copy Link --}}
                        <button
                            type="button"
                            class="p-2 rounded-lg text-gray-300 hover:text-white hover:bg-white/10 transition-colors"
                            x-on:click="navigator.clipboard.writeText('{{ $fullUrl }}'); $dispatch('notify', { status: 'success', message: '{{ trans('filament-media::media.link_copied') }}' })"
                            title="{{ trans('filament-media::media.copy_link') }}"
                        >
                            <x-filament::icon icon="heroicon-m-link" class="w-5 h-5" />
                        </button>
                    @endif

                    {{-- Close --}}
                    <button
                        type="button"
                        class="p-2 rounded-lg text-gray-300 hover:text-white hover:bg-white/10 transition-colors"
                        x-on:click="$wire.close()"
                        title="{{ trans('filament-media::media.close') }}"
                    >
                        <x-filament::icon icon="heroicon-m-x-mark" class="w-5 h-5" />
                    </button>
                </div>
            </header>

            {{-- Main Preview Area --}}
            <main class="flex-1 relative flex items-center justify-center p-4 overflow-hidden">
                {{-- Previous Button --}}
                @if($this->hasPrevious())
                    <button
                        type="button"
                        wire:click="previous"
                        class="absolute left-4 z-10 p-3 rounded-full bg-black/50 text-white hover:bg-black/70 transition-colors"
                        title="{{ trans('filament-media::media.preview_previous') }}"
                    >
                        <x-filament::icon icon="heroicon-m-chevron-left" class="w-6 h-6" />
                    </button>
                @endif

                {{-- Preview Content --}}
                <div class="max-w-full max-h-full flex items-center justify-center">
                    @if(!$fileExists)
                        {{-- Missing File State --}}
                        <div class="flex flex-col items-center gap-6 p-8 bg-gray-900 rounded-xl max-w-md">
                            <div class="w-24 h-24 rounded-full bg-red-900/30 flex items-center justify-center">
                                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-12 h-12 text-red-500" />
                            </div>
                            <div class="text-center">
                                <h3 class="text-lg font-medium text-white mb-2">{{ trans('filament-media::media.file_missing') }}</h3>
                                <p class="text-sm text-gray-400 mb-1">{{ $name }}</p>
                                <p class="text-xs text-gray-500">{{ trans('filament-media::media.file_missing_description') }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                @if($currentFileId)
                                <button
                                    type="button"
                                    wire:click="$dispatch('open-delete-modal', { items: [{ id: {{ $currentFileId }}, is_folder: false }] })"
                                    x-on:click="$wire.close()"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-500 text-white rounded-lg transition-colors text-sm"
                                >
                                    <x-filament::icon icon="heroicon-m-trash" class="w-4 h-4" />
                                    {{ trans('filament-media::media.delete') }}
                                </button>
                                @endif
                                <button
                                    type="button"
                                    x-on:click="$wire.close()"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors text-sm"
                                >
                                    {{ trans('filament-media::media.close') }}
                                </button>
                            </div>
                        </div>
                    @elseif($fileType === 'image')
                        <img
                            src="{{ $fullUrl }}"
                            alt="{{ $alt ?: $name }}"
                            class="max-w-full max-h-[calc(100vh-12rem)] object-contain rounded-lg"
                            loading="eager"
                        />
                    @elseif($fileType === 'video')
                        <video
                            src="{{ $fullUrl }}"
                            controls
                            autoplay
                            class="max-w-full max-h-[calc(100vh-12rem)] rounded-lg"
                        >
                            {{ trans('filament-media::media.video_not_supported') }}
                        </video>
                    @elseif($fileType === 'audio')
                        <div class="flex flex-col items-center gap-6 p-8 bg-gray-900 rounded-xl">
                            <div class="w-24 h-24 rounded-full bg-gradient-to-br from-pink-500 to-purple-600 flex items-center justify-center">
                                <x-filament::icon icon="heroicon-o-musical-note" class="w-12 h-12 text-white" />
                            </div>
                            <div class="text-center">
                                <h3 class="text-lg font-medium text-white mb-1">{{ $name }}</h3>
                                <p class="text-sm text-gray-400">{{ $size }}</p>
                            </div>
                            <audio
                                src="{{ $fullUrl }}"
                                controls
                                autoplay
                                class="w-80"
                            >
                                {{ trans('filament-media::media.audio_not_supported') }}
                            </audio>
                        </div>
                    @else
                        {{-- Document / Unknown --}}
                        <div class="flex flex-col items-center gap-6 p-8 bg-gray-900 rounded-xl">
                            <div class="w-24 h-24 rounded-xl bg-gray-800 flex items-center justify-center">
                                <x-filament::icon icon="heroicon-o-document-text" class="w-12 h-12 text-gray-400" />
                            </div>
                            <div class="text-center">
                                <h3 class="text-lg font-medium text-white mb-1">{{ $name }}</h3>
                                <p class="text-sm text-gray-400 mb-4">{{ $size }} &middot; {{ $mimeType }}</p>
                            </div>
                            <a
                                href="{{ $fullUrl }}"
                                download
                                class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-500 text-white rounded-lg transition-colors"
                            >
                                <x-filament::icon icon="heroicon-m-arrow-down-tray" class="w-5 h-5" />
                                {{ trans('filament-media::media.download') }}
                            </a>
                        </div>
                    @endif
                </div>

                {{-- Next Button --}}
                @if($this->hasNext())
                    <button
                        type="button"
                        wire:click="next"
                        class="absolute right-4 z-10 p-3 rounded-full bg-black/50 text-white hover:bg-black/70 transition-colors"
                        title="{{ trans('filament-media::media.preview_next') }}"
                    >
                        <x-filament::icon icon="heroicon-m-chevron-right" class="w-6 h-6" />
                    </button>
                @endif
            </main>

            {{-- Thumbnail Strip --}}
            @if(count($thumbnails) > 1)
                <footer class="flex-shrink-0 bg-gray-900/90 backdrop-blur-sm border-t border-white/10 px-4 py-3">
                    <div class="flex items-center justify-center gap-2 overflow-x-auto max-w-full">
                        @foreach($thumbnails as $index => $thumb)
                            <button
                                type="button"
                                wire:click="goToIndex({{ $index }})"
                                class="flex-shrink-0 w-12 h-12 rounded-lg overflow-hidden border-2 transition-all {{ $thumb['is_current'] ? 'border-primary-500 ring-2 ring-primary-500/50' : 'border-transparent hover:border-gray-500' }}"
                            >
                                @if($thumb['thumbnail'] && str_starts_with($thumb['mime_type'] ?? '', 'image/'))
                                    <img
                                        src="{{ $thumb['thumbnail'] }}"
                                        alt="{{ $thumb['name'] }}"
                                        class="w-full h-full object-cover"
                                    />
                                @else
                                    @php
                                        $iconColor = match(true) {
                                            str_starts_with($thumb['mime_type'] ?? '', 'video/') => 'text-purple-400',
                                            str_starts_with($thumb['mime_type'] ?? '', 'audio/') => 'text-pink-400',
                                            default => 'text-gray-400',
                                        };
                                        $icon = match(true) {
                                            str_starts_with($thumb['mime_type'] ?? '', 'video/') => 'heroicon-o-film',
                                            str_starts_with($thumb['mime_type'] ?? '', 'audio/') => 'heroicon-o-musical-note',
                                            default => 'heroicon-o-document',
                                        };
                                    @endphp
                                    <div class="w-full h-full bg-gray-800 flex items-center justify-center">
                                        <x-filament::icon :icon="$icon" class="w-6 h-6 {{ $iconColor }}" />
                                    </div>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    {{-- Counter --}}
                    <div class="text-center mt-2 text-sm text-gray-400">
                        {{ $currentIndex + 1 }} / {{ count($thumbnails) }}
                    </div>
                </footer>
            @endif
        </div>
    </div>
</div>
