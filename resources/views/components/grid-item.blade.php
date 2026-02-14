@php
    $isFolder = $item['is_folder'] ?? false;
    $isSelected = collect($selectedItems)->contains(
        fn($i) => $i['id'] === $item['id'] && ($i['is_folder'] ?? false) === $isFolder,
    );
    $itemData = ['id' => $item['id'], 'is_folder' => $isFolder];
    $fileExists = $item['file_exists'] ?? true; // Default to true for folders
@endphp

<div x-data="{
    isDragOver: false,
    isDragging: false,
}"
    class="fm-item group relative rounded-lg overflow-hidden bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 transition-all duration-150 cursor-pointer
        hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-md
        {{ $isSelected ? 'ring-2 ring-primary-500 ring-offset-2 dark:ring-offset-gray-900 border-primary-500' : '' }}"
    :class="{
        'opacity-50': isDragging,
        'ring-2 ring-primary-500 ring-offset-2 dark:ring-offset-gray-900 border-primary-500 bg-primary-50 dark:bg-primary-900/20': isDragOver &&
            {{ $isFolder ? 'true' : 'false' }}
    }"
    wire:key="item-{{ $item['id'] }}-{{ $isFolder ? 'folder' : 'file' }}" data-item-index="{{ $index }}"
    data-item-id="{{ $item['id'] }}" data-is-folder="{{ $isFolder ? '1' : '0' }}" draggable="true"
    x-on:dragstart="
        isDragging = true;
        $event.dataTransfer.effectAllowed = 'move';
        $event.dataTransfer.setData('application/json', JSON.stringify({{ json_encode($itemData) }}));
        $event.dataTransfer.setData('text/plain', '{{ $item['name'] }}');
    "
    x-on:dragend="isDragging = false"
    @if ($isFolder) x-on:dragover.prevent="isDragOver = true; $event.dataTransfer.dropEffect = 'move'"
    x-on:dragleave.prevent="isDragOver = false"
    x-on:drop.prevent="
        isDragOver = false;
        try {
            const data = JSON.parse($event.dataTransfer.getData('application/json'));
            if (data && data.id !== {{ $item['id'] }}) {
                $wire.moveItemsToFolder([data], {{ $item['id'] }});
            }
        } catch (e) {
            console.error('Drop failed:', e);
        }
    " @endif
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
        contextMenu = { show: true, x: $event.clientX, y: $event.clientY, item: {{ Js::from($item) }} };
    "
    role="button" tabindex="0" aria-label="{{ $item['name'] }}"
    aria-selected="{{ $isSelected ? 'true' : 'false' }}">
    {{-- Selection Indicator --}}
    <div
        class="absolute top-2 left-2 z-10 transition-opacity {{ $isSelected ? 'opacity-100' : 'opacity-0 group-hover:opacity-100' }}">
        <div
            class="w-5 h-5 rounded-full flex items-center justify-center {{ $isSelected ? 'bg-primary-500' : 'bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600' }}">
            @if ($isSelected)
                <x-filament::icon icon="heroicon-m-check" class="w-3 h-3 text-white" />
            @endif
        </div>
    </div>

    {{-- Thumbnail Area --}}
    <div class="aspect-square bg-gray-100 dark:bg-gray-900 flex items-center justify-center overflow-hidden relative">
        @if ($isFolder)
            {{-- Folder Icon --}}
            <div class="w-16 h-16 flex items-center justify-center"
                style="{{ isset($item['color']) && $item['color'] ? 'color: ' . $item['color'] : '' }}">
                <x-filament::icon icon="heroicon-s-folder"
                    class="w-16 h-16 {{ isset($item['color']) && $item['color'] ? '' : 'text-amber-500' }}" />
            </div>
        @elseif(!$fileExists)
            @include('filament-media::components.missing-file')
        @elseif(isset($item['thumb']) && $item['thumb'])
            {{-- Image Thumbnail --}}
            <img src="{{ $item['thumb'] }}" alt="{{ $item['name'] }}"
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                loading="lazy" />
        @elseif(($item['type'] ?? '') === 'image')
            {{-- Image without thumbnail --}}
            <img src="{{ $item['full_url'] ?? ($item['url'] ?? '') }}" alt="{{ $item['name'] }}"
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                loading="lazy" />
        @else
            {{-- File Type Icon --}}
            @php
                $iconColor = match ($item['type'] ?? 'document') {
                    'image' => 'text-blue-500',
                    'video' => 'text-purple-500',
                    'audio' => 'text-pink-500',
                    'document' => 'text-red-500',
                    default => 'text-gray-400',
                };
                $icon = match ($item['type'] ?? 'document') {
                    'image' => 'heroicon-o-photo',
                    'video' => 'heroicon-o-film',
                    'audio' => 'heroicon-o-musical-note',
                    'document' => 'heroicon-o-document-text',
                    default => 'heroicon-o-document',
                };
            @endphp
            <x-filament::icon :icon="$icon" class="w-12 h-12 {{ $iconColor }}" />
        @endif

        {{-- Video Duration Badge --}}
        @if (($item['type'] ?? '') === 'video' && isset($item['duration']))
            <div class="absolute bottom-2 right-2 px-1.5 py-0.5 bg-black/70 rounded text-xs text-white font-medium">
                {{ $item['duration'] }}
            </div>
        @endif

    </div>

    {{-- File Name --}}
    <div class="p-3 border-t border-gray-100 dark:border-gray-700">
        <p class="text-sm font-medium text-gray-900 dark:text-white truncate" title="{{ $item['name'] }}">
            {{ $item['name'] }}
        </p>
        @if (!$isFolder && isset($item['size']))
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                {{ $item['size'] }}
            </p>
        @endif
    </div>

    {{-- Quick Action Buttons (visible on hover) --}}
    <div class="absolute top-2 right-2 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
        @if (!$isFolder && $fileExists)
            <button type="button"
                class="w-7 h-7 rounded-full bg-white dark:bg-gray-800 shadow-md flex items-center justify-center text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                x-on:click.stop="$wire.openItem({{ json_encode($itemData) }})"
                title="{{ trans('filament-media::media.preview') }}">
                <x-filament::icon icon="heroicon-m-eye" class="w-4 h-4" />
            </button>
        @endif
        <button type="button"
            class="w-7 h-7 rounded-full bg-white dark:bg-gray-800 shadow-md flex items-center justify-center text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
            x-on:click.stop="contextMenu = { show: true, x: $event.clientX, y: $event.clientY, item: {{ Js::from($item) }} }"
            title="{{ trans('filament-media::media.more_options') }}">
            <x-filament::icon icon="heroicon-m-ellipsis-vertical" class="w-4 h-4" />
        </button>
    </div>
</div>
