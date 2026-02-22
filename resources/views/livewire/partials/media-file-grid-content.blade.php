<div class="grid {{ $columns }} gap-4">
    @foreach ($this->files as $file)
        @php
            $itemData = $this->buildItemData($file);
        @endphp
        <div class="group relative bg-white dark:bg-black rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:border-primary-300 dark:hover:border-primary-500/50 hover:shadow-md transition-all duration-200"
            wire:key="file-{{ $file->id }}"
            @if ($contextMenu) x-on:contextmenu.prevent="openContextMenu($event, @js($itemData))" @endif>
            <a href="{{ $file->indirect_url }}" target="_blank" class="block">
                <div class="aspect-video bg-gray-100 dark:bg-gray-700/50 flex items-center justify-center">
                    @if ($file->type === 'image')
                        <img src="{{ $file->indirect_url }}" alt="{{ $file->name }}"
                            class="w-full h-full object-contain">
                    @else
                        @php
                            $iconColor = match ($file->type) {
                                'image' => 'text-blue-500',
                                'video' => 'text-purple-500',
                                'audio' => 'text-pink-500',
                                'document' => 'text-red-500',
                                default => 'text-gray-400 dark:text-gray-500',
                            };
                            $icon = match ($file->type) {
                                'image' => 'heroicon-o-photo',
                                'video' => 'heroicon-o-film',
                                'audio' => 'heroicon-o-musical-note',
                                'document' => 'heroicon-o-document-text',
                                default => 'heroicon-o-document',
                            };
                        @endphp
                        <x-filament::icon :icon="$icon" class="w-12 h-12 {{ $iconColor }}" />
                    @endif
                </div>
                <div class="p-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                        {{ $file->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ strtoupper(pathinfo($file->url, PATHINFO_EXTENSION)) }}
                        @if ($file->size)
                            · {{ Number::fileSize($file->size) }}
                        @endif
                    </p>
                    @if ($file->user || $file->created_at)
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                            {{ $file->user?->name ?? __('Unknown') }}
                            @if ($file->created_at)
                                · {{ $file->created_at->translatedFormat('d M Y') }}
                            @endif
                        </p>
                    @endif
                </div>
            </a>

            {{-- Hover overlay with actions --}}
            <div
                class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                <a href="{{ $file->indirect_url }}" target="_blank"
                    class="p-2 rounded-lg bg-white/90 hover:bg-white transition-colors"
                    title="{{ trans('filament-media::media.preview') }}">
                    <x-heroicon-m-eye class="w-5 h-5 text-gray-700" />
                </a>
                <a href="{{ $file->indirect_url }}" download
                    class="p-2 rounded-lg bg-white/90  hover:bg-white transition-colors"
                    title="{{ trans('filament-media::media.download') }}">
                    <x-heroicon-m-arrow-down-tray class="w-5 h-5 text-gray-700" />
                </a>
                @if ($deletable)
                    <button type="button"
                        x-on:click.prevent.stop="$wire.mountAction('trash', { items: [{ id: {{ $file->id }}, is_folder: false }] })"
                        class="p-2 rounded-lg bg-white/90  hover:bg-white transition-colors"
                        title="{{ trans('filament-media::media.move_to_trash') }}">
                        <x-heroicon-m-trash class="w-5 h-5 text-red-600" />
                    </button>
                @endif
            </div>
        </div>
    @endforeach
</div>
