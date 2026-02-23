{{-- Upload progress overlay for drag & drop areas --}}
<div
    x-show="isUploading"
    x-cloak
    x-transition:enter="ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-2 bg-black/60 rounded-lg"
>
    {{-- Circular progress ring --}}
    <div class="relative w-14 h-14">
        <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="currentColor" stroke-width="2" class="text-white/20" />
            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                class="text-primary-400 transition-all duration-300"
                :stroke-dasharray="uploadProgress + ' 100'"
            />
        </svg>
        <span class="absolute inset-0 flex items-center justify-center text-sm font-semibold text-white" x-text="uploadProgress + '%'"></span>
    </div>
    <span class="text-xs text-white/80 font-medium">{{ trans('filament-media::media.upload_progress') }}</span>
</div>
