{{-- Missing File Placeholder --}}
<div class="flex flex-col items-center justify-center text-center p-2">
    <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-gray-900 flex items-center justify-center mb-2">
        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-6 h-6 text-red-500 dark:text-red-400" />
    </div>
    <span
        class="text-xs text-red-600 dark:text-red-400 font-medium">{{ trans('filament-media::media.file_missing') }}</span>
</div>
