@props(['urls' => [], 'alt' => ''])

@if (count($urls) > 0)
    <div x-data="{
        images: @js($urls),
        index: 0,
        fullscreen: false,
        get current() { return this.images[this.index] },
        next() { this.index = (this.index + 1) % this.images.length },
        prev() { this.index = (this.index - 1 + this.images.length) % this.images.length },
        goTo(i) { this.index = i },
        open() { this.fullscreen = true; document.body.style.overflow = 'hidden' },
        close() { this.fullscreen = false; document.body.style.overflow = '' },
    }" x-on:keydown.right.window="if (fullscreen) next()"
        x-on:keydown.left.window="if (fullscreen) prev()"
        x-on:keydown.escape.window="close()"
        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">

        {{-- Main Image --}}
        <div class="relative group cursor-pointer" @click="open()">
            <img :src="current" alt="{{ $alt }}" class="w-full h-96 object-cover">

            {{-- Image Counter --}}
            <div class="absolute top-3 end-3 bg-black/60 text-white text-xs px-2.5 py-1 rounded-full">
                <span x-text="(index + 1) + ' / ' + images.length"></span>
            </div>

            {{-- Prev/Next Arrows --}}
            @if (count($urls) > 1)
                <button @click.stop="prev()"
                    class="absolute start-3 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                    aria-label="{{ __('Previous image') }}">
                    <svg class="w-5 h-5 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button @click.stop="next()"
                    class="absolute end-3 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                    aria-label="{{ __('Next image') }}">
                    <svg class="w-5 h-5 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            @endif
        </div>

        {{-- Thumbnails --}}
        @if (count($urls) > 1)
            <div class="p-3 flex space-x-2 rtl:space-x-reverse overflow-x-auto">
                @foreach ($urls as $i => $url)
                    <img src="{{ $url }}" alt="" @click="goTo({{ $i }})"
                        :class="{{ $i }} === index ? 'ring-2 ring-brand-500 opacity-100' : 'opacity-60 hover:opacity-100'"
                        class="w-20 h-20 object-cover rounded cursor-pointer transition-all duration-200 shrink-0">
                @endforeach
            </div>
        @endif

        {{-- Fullscreen Lightbox --}}
        <div x-show="fullscreen" x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            @click="close()"
            class="fixed inset-0 z-[9999] bg-black/95 flex flex-col items-center justify-center"
            style="display: none;">

            {{-- Close Button --}}
            <button @click="close()" class="absolute top-4 end-4 text-white/80 hover:text-white p-2 z-10"
                aria-label="{{ __('Close') }}">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            {{-- Title & Counter --}}
            <div class="absolute top-4 start-4 end-16 z-10">
                @if ($alt)
                    <h2 class="text-white font-semibold text-lg truncate">{{ $alt }}</h2>
                @endif
                <span class="text-white/60 text-sm" x-text="(index + 1) + ' / ' + images.length"></span>
            </div>

            {{-- Image --}}
            <div class="flex-1 flex items-center justify-center w-full px-16" @click.stop>
                <img :src="current" alt="{{ $alt }}" class="max-h-[80vh] max-w-full object-contain select-none">
            </div>

            {{-- Prev/Next --}}
            @if (count($urls) > 1)
                <button @click.stop="prev()"
                    class="absolute start-4 top-1/2 -translate-y-1/2 bg-white/10 hover:bg-white/25 text-white rounded-full p-3 transition-colors"
                    aria-label="{{ __('Previous image') }}">
                    <svg class="w-6 h-6 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button @click.stop="next()"
                    class="absolute end-4 top-1/2 -translate-y-1/2 bg-white/10 hover:bg-white/25 text-white rounded-full p-3 transition-colors"
                    aria-label="{{ __('Next image') }}">
                    <svg class="w-6 h-6 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                {{-- Thumbnail Strip --}}
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex space-x-2 rtl:space-x-reverse max-w-[90vw] overflow-x-auto p-2" @click.stop>
                    @foreach ($urls as $i => $url)
                        <img src="{{ $url }}" alt="" @click="goTo({{ $i }})"
                            :class="{{ $i }} === index ? 'ring-2 ring-white opacity-100' : 'opacity-40 hover:opacity-80'"
                            class="w-16 h-16 object-cover rounded cursor-pointer transition-all duration-200 shrink-0">
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@else
    {{-- No Images Placeholder --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
        <div class="w-full h-96 flex items-center justify-center bg-gray-100 dark:bg-gray-700">
            <svg class="w-24 h-24 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
            </svg>
        </div>
    </div>
@endif
