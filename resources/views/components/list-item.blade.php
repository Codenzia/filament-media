@php
    $isFolder = $item['is_folder'] ?? false;
    $isSelected = collect($selectedItems)->contains(
        fn($i) => $i['id'] === $item['id'] && ($i['is_folder'] ?? false) === $isFolder,
    );
    $itemData = ['id' => $item['id'], 'is_folder' => $isFolder];
    $fileExists = $item['file_exists'] ?? true; // Default to true for folders
@endphp

<div class="fm-item grid grid-cols-12 gap-4 px-4 py-3 items-center transition-all cursor-pointer
        {{ $isSelected ? 'bg-primary-50 dark:bg-primary-900/20' : '' }}"
    wire:key="list-item-{{ $item['id'] }}-{{ $isFolder ? 'folder' : 'file' }}" data-item-index="{{ $index }}"
    x-on:click="
        if (ctrlKey) {
            $wire.selectItem({{ json_encode($itemData) }}, true);
        } else {
            $wire.selectItem({{ json_encode($itemData) }});
        }
        lastSelectedIndex = {{ $index }};
        focusedIndex = {{ $index }};
    "
    x-on:dblclick="$wire.openItem({{ json_encode($itemData) }})"
    x-on:contextmenu.prevent="
        $wire.selectItem({{ json_encode($itemData) }});
        bgContextMenu.show = false;
        contextMenu = { show: true, x: $event.clientX, y: $event.clientY, item: {{ Js::from($item) }} };
    "
    role="row" tabindex="0" aria-label="{{ $item['name'] }}" aria-selected="{{ $isSelected ? 'true' : 'false' }}">
    {{-- Name Column --}}
    <div class="col-span-12 sm:col-span-6 flex items-center gap-3 min-w-0">
        {{-- Checkbox --}}
        <div class="flex-shrink-0">
            <div
                class="w-5 h-5 rounded flex items-center justify-center {{ $isSelected ? 'bg-primary-500' : 'border border-gray-300 dark:border-gray-600' }}">
                @if ($isSelected)
                    <x-filament::icon icon="heroicon-m-check" class="w-3 h-3 text-white" />
                @endif
            </div>
        </div>

        {{-- Thumbnail/Icon --}}
        <div
            class="flex-shrink-0 w-10 h-10 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800 flex items-center justify-center relative">
            @if ($isFolder)
                <x-filament::icon icon="heroicon-s-folder"
                    class="w-6 h-6 {{ isset($item['color']) && $item['color'] ? '' : 'text-amber-500' }}"
                    style="{{ isset($item['color']) && $item['color'] ? 'color: ' . $item['color'] : '' }}" />
            @elseif(!$fileExists)
                {{-- Missing File Indicator --}}
                <div class="w-full h-full bg-red-100 dark:bg-gray-900 flex items-center justify-center">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle"
                        class="w-5 h-5 text-red-500 dark:text-red-400" />
                </div>
            @elseif(isset($item['thumb']) && $item['thumb'])
                <img src="{{ $item['thumb'] }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover"
                    loading="lazy" />
            @elseif(($item['type'] ?? '') === 'image' && isset($item['full_url']))
                <img src="{{ $item['full_url'] }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover"
                    loading="lazy" />
            @else
                @php
                    $iconColor = match ($item['type'] ?? 'document') {
                        'image' => 'text-blue-500',
                        'video' => 'text-purple-500',
                        'audio' => 'text-pink-500',
                        'document' => 'text-red-500',
                        default => 'text-gray-900 dark:text-gray-400',
                    };
                    $icon = match ($item['type'] ?? 'document') {
                        'image' => 'heroicon-o-photo',
                        'video' => 'heroicon-o-film',
                        'audio' => 'heroicon-o-musical-note',
                        'document' => 'heroicon-o-document-text',
                        default => 'heroicon-o-document',
                    };
                @endphp
                <x-filament::icon :icon="$icon" class="w-5 h-5 {{ $iconColor }}" />
            @endif
        </div>

        {{-- Name --}}
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate" title="{{ $item['name'] }}">
                    {{ $item['name'] }}
                </p>
            </div>
            {{-- Mobile: Show size and date inline --}}
            <p class="sm:hidden text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                @if (!$isFolder && !$fileExists)
                    <span class="text-red-500">{{ trans('filament-media::media.file_missing') }}</span>
                @elseif(isset($item['size']) && $item['size'])
                    {{ $item['size'] }}
                @endif
                @if (isset($item['created_at']))
                    <span class="mx-1">•</span>
                    {{ \Carbon\Carbon::parse($item['created_at'])->diffForHumans() }}
                @endif
            </p>
        </div>
    </div>

    {{-- Size Column --}}
    <div class="hidden sm:block col-span-2 text-sm text-gray-500 dark:text-gray-400">
        @if (isset($item['size']) && $item['size'])
            {{ $item['size'] }}
        @else
            —
        @endif
    </div>

    {{-- Type Column --}}
    <div class="hidden sm:block col-span-2 text-sm text-gray-500 dark:text-gray-400">
        @if ($isFolder)
            @if (isset($item['total_file_count']) && $item['total_file_count'] !== null)
                @if (isset($item['filtered_file_count']) && $item['filtered_file_count'] !== $item['total_file_count'])
                    {{ trans('filament-media::media.filtered_file_count', ['filtered' => $item['filtered_file_count'], 'total' => $item['total_file_count']]) }}
                @else
                    {{ trans('filament-media::media.folder_file_count', ['count' => $item['total_file_count']]) }}
                @endif
            @else
                {{ trans('filament-media::media.folder') }}
            @endif
        @else
            {{ strtoupper($item['type'] ?? 'File') }}
        @endif
    </div>

    {{-- Modified Column --}}
    <div class="hidden sm:block col-span-2 text-sm text-gray-500 dark:text-gray-400">
        @if (isset($item['created_at']))
            {{ \Carbon\Carbon::parse($item['created_at'])->format('M d, Y') }}
        @else
            —
        @endif
    </div>
</div>
