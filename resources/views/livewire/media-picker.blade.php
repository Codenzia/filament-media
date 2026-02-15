@php
    use Codenzia\FilamentMedia\Services\MediaUrlService;
@endphp

<div
    x-data="{
        viewMode: @entangle('viewMode'),
    }"
    class="flex flex-col h-[70vh] bg-white dark:bg-gray-900 rounded-xl overflow-hidden"
>
    {{-- Header --}}
    <div class="flex-shrink-0 flex items-center gap-3 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
        {{-- Search --}}
        <div class="flex-1 relative">
            <x-filament::icon icon="heroicon-m-magnifying-glass" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ trans('filament-media::media.search_file_and_folder') }}"
                class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            />
        </div>

        {{-- Filter --}}
        <select
            wire:model.live="filter"
            class="text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500"
        >
            <option value="everything">{{ trans('filament-media::media.everything') }}</option>
            <option value="image">{{ trans('filament-media::media.image') }}</option>
            <option value="video">{{ trans('filament-media::media.video') }}</option>
            <option value="document">{{ trans('filament-media::media.document') }}</option>
        </select>

        {{-- View Mode Toggle --}}
        <div class="flex items-center rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
            <button
                type="button"
                class="p-2 transition-colors"
                :class="viewMode === 'grid' ? 'bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                wire:click="$set('viewMode', 'grid')"
            >
                <x-filament::icon icon="heroicon-m-squares-2x2" class="w-4 h-4" />
            </button>
            <button
                type="button"
                class="p-2 transition-colors"
                :class="viewMode === 'list' ? 'bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                wire:click="$set('viewMode', 'list')"
            >
                <x-filament::icon icon="heroicon-m-list-bullet" class="w-4 h-4" />
            </button>
        </div>
    </div>

    {{-- Breadcrumbs --}}
    <div class="flex-shrink-0 flex items-center gap-1 px-4 py-2 text-sm border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 overflow-x-auto">
        @foreach($breadcrumbs as $i => $crumb)
            @if($i > 0)
                <x-filament::icon icon="heroicon-m-chevron-right" class="w-3 h-3 text-gray-400 flex-shrink-0" />
            @endif
            <button
                type="button"
                wire:click="openFolder({{ $crumb['id'] }})"
                class="flex-shrink-0 text-gray-600 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors {{ $loop->last ? 'font-medium' : '' }}"
            >
                {{ $crumb['name'] }}
            </button>
        @endforeach
    </div>

    {{-- Content Area --}}
    <div class="flex-1 overflow-y-auto p-4">
        @if($folders->isEmpty() && $files->isEmpty())
            <div class="flex flex-col items-center justify-center h-full text-gray-400">
                <x-filament::icon icon="heroicon-o-folder-open" class="w-12 h-12 mb-2" />
                <p class="text-sm">{{ trans('filament-media::media.no_search_results') }}</p>
            </div>
        @else
            {{-- Grid View --}}
            <div x-show="viewMode === 'grid'" class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-3">
                {{-- Folders --}}
                @foreach($folders as $folder)
                    <button
                        type="button"
                        wire:click="openFolder({{ $folder->id }})"
                        class="flex flex-col items-center gap-1 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    >
                        <x-filament::icon icon="heroicon-s-folder"
                            class="w-10 h-10 {{ $folder->color ? '' : 'text-amber-500' }}"
                            style="{{ $folder->color ? 'color: ' . $folder->color : '' }}"
                        />
                        <span class="text-xs text-gray-700 dark:text-gray-300 truncate w-full text-center">{{ $folder->name }}</span>
                    </button>
                @endforeach

                {{-- Files --}}
                @foreach($files as $file)
                    @php
                        $isSelected = in_array($file->id, $selected);
                        $urlService = app(MediaUrlService::class);
                        $canThumb = $file->canGenerateThumbnails() && $urlService->fileExists($file->url);
                    @endphp
                    <button
                        type="button"
                        wire:click="selectFile({{ $file->id }})"
                        class="relative flex flex-col items-center gap-1 p-2 rounded-lg border-2 transition-all {{ $isSelected ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-transparent hover:bg-gray-100 dark:hover:bg-gray-800' }}"
                    >
                        @if($isSelected)
                            <div class="absolute top-1 right-1 w-5 h-5 rounded-full bg-primary-500 flex items-center justify-center">
                                <x-filament::icon icon="heroicon-m-check" class="w-3 h-3 text-white" />
                            </div>
                        @endif

                        <div class="w-full aspect-square rounded bg-gray-100 dark:bg-gray-800 flex items-center justify-center overflow-hidden">
                            @if($canThumb)
                                <img src="{{ $file->url }}" alt="{{ $file->name }}" class="w-full h-full object-cover" />
                            @else
                                @php
                                    $icon = match($file->type) {
                                        'image' => 'heroicon-o-photo',
                                        'video' => 'heroicon-o-film',
                                        'audio' => 'heroicon-o-musical-note',
                                        default => 'heroicon-o-document',
                                    };
                                @endphp
                                <x-filament::icon :icon="$icon" class="w-8 h-8 text-gray-400" />
                            @endif
                        </div>
                        <span class="text-xs text-gray-700 dark:text-gray-300 truncate w-full text-center">{{ $file->name }}</span>
                    </button>
                @endforeach
            </div>

            {{-- List View --}}
            <div x-show="viewMode === 'list'" x-cloak class="space-y-1">
                {{-- Folders --}}
                @foreach($folders as $folder)
                    <button
                        type="button"
                        wire:click="openFolder({{ $folder->id }})"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    >
                        <x-filament::icon icon="heroicon-s-folder"
                            class="w-6 h-6 flex-shrink-0 {{ $folder->color ? '' : 'text-amber-500' }}"
                            style="{{ $folder->color ? 'color: ' . $folder->color : '' }}"
                        />
                        <span class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ $folder->name }}</span>
                    </button>
                @endforeach

                {{-- Files --}}
                @foreach($files as $file)
                    @php $isSelected = in_array($file->id, $selected); @endphp
                    <button
                        type="button"
                        wire:click="selectFile({{ $file->id }})"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ $isSelected ? 'bg-primary-50 dark:bg-primary-900/20 ring-1 ring-primary-500' : 'hover:bg-gray-100 dark:hover:bg-gray-800' }}"
                    >
                        @if($isSelected)
                            <div class="w-5 h-5 rounded-full bg-primary-500 flex items-center justify-center flex-shrink-0">
                                <x-filament::icon icon="heroicon-m-check" class="w-3 h-3 text-white" />
                            </div>
                        @else
                            <div class="w-5 h-5 rounded-full border-2 border-gray-300 dark:border-gray-600 flex-shrink-0"></div>
                        @endif

                        @php
                            $icon = match($file->type) {
                                'image' => 'heroicon-o-photo',
                                'video' => 'heroicon-o-film',
                                'audio' => 'heroicon-o-musical-note',
                                default => 'heroicon-o-document',
                            };
                        @endphp
                        <x-filament::icon :icon="$icon" class="w-5 h-5 text-gray-400 flex-shrink-0" />

                        <span class="text-sm text-gray-700 dark:text-gray-300 truncate flex-1 text-left">{{ $file->name }}</span>
                        <span class="text-xs text-gray-400 flex-shrink-0">{{ $file->human_size }}</span>
                    </button>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($files->hasPages())
                <div class="mt-4">
                    {{ $files->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Footer --}}
    <div class="flex-shrink-0 flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
        <span class="text-sm text-gray-500 dark:text-gray-400">
            @if(count($selected) > 0)
                {{ count($selected) }} {{ trans('filament-media::media.selected') }}
            @else
                &nbsp;
            @endif
        </span>

        <div class="flex items-center gap-2">
            <x-filament::button color="gray" wire:click="cancel" type="button">
                {{ trans('filament-media::media.cancel') }}
            </x-filament::button>

            <x-filament::button
                wire:click="confirm"
                :disabled="empty($selected)"
            >
                {{ trans('filament-media::media.confirm') }}
            </x-filament::button>
        </div>
    </div>
</div>
