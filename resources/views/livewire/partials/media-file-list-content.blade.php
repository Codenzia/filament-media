{{-- List Header --}}
<div class="hidden sm:grid grid-cols-12 gap-4 px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
    <div class="col-span-5">{{ trans('filament-media::media.name') }}</div>
    <div class="col-span-2">{{ trans('filament-media::media.size') }}</div>
    <div class="col-span-2">{{ trans('filament-media::media.type') }}</div>
    <div class="col-span-2">{{ trans('filament-media::media.modified') }}</div>
    <div class="col-span-1"></div>
</div>

{{-- List Items --}}
<div class="divide-y divide-gray-200 dark:divide-gray-700">
    @foreach ($this->files as $file)
        @php
            $itemData = $this->buildItemData($file);
        @endphp
        <div class="grid grid-cols-12 gap-4 px-4 py-3 items-center hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group"
            wire:key="file-{{ $file->id }}"
            @if ($contextMenu) x-on:contextmenu.prevent="openContextMenu($event, @js($itemData))" @endif>

            {{-- Name Column (with thumbnail/icon) --}}
            <div class="col-span-12 sm:col-span-5 flex items-center gap-3 min-w-0">
                {{-- Thumbnail/Icon --}}
                <div class="flex-shrink-0 w-10 h-10 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                    @if ($file->type === 'image')
                        <img src="{{ $file->indirect_url }}" alt="{{ $file->name }}"
                            class="w-full h-full object-cover" loading="lazy" />
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
                        <x-filament::icon :icon="$icon" class="w-5 h-5 {{ $iconColor }}" />
                    @endif
                </div>

                {{-- Name --}}
                <div class="min-w-0 flex-1">
                    <a href="{{ $file->indirect_url }}" target="_blank"
                        class="text-sm font-medium text-gray-900 dark:text-white truncate block hover:text-primary-600 dark:hover:text-primary-400"
                        title="{{ $file->name }}">
                        {{ $file->name }}
                    </a>
                    {{-- Mobile: inline size and date --}}
                    <p class="sm:hidden text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        @if ($file->size)
                            {{ Number::fileSize($file->size) }}
                        @endif
                        @if ($file->created_at)
                            <span class="mx-1">&middot;</span>
                            {{ $file->created_at->diffForHumans() }}
                        @endif
                    </p>
                </div>
            </div>

            {{-- Size Column --}}
            <div class="hidden sm:block col-span-2 text-sm text-gray-500 dark:text-gray-400">
                @if ($file->size)
                    {{ Number::fileSize($file->size) }}
                @else
                    &mdash;
                @endif
            </div>

            {{-- Type Column --}}
            <div class="hidden sm:block col-span-2 text-sm text-gray-500 dark:text-gray-400">
                {{ strtoupper(pathinfo($file->url, PATHINFO_EXTENSION)) }}
            </div>

            {{-- Modified Column --}}
            <div class="hidden sm:block col-span-2 text-sm text-gray-500 dark:text-gray-400">
                @if ($file->created_at)
                    {{ $file->created_at->translatedFormat('M d, Y') }}
                @else
                    &mdash;
                @endif
            </div>

            {{-- Inline Action Buttons (visible on hover) --}}
            <div class="hidden sm:flex col-span-1 items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <a href="{{ $file->indirect_url }}" target="_blank"
                    class="p-1.5 rounded-md text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    title="{{ trans('filament-media::media.preview') }}">
                    <x-heroicon-m-eye class="w-4 h-4" />
                </a>
                <a href="{{ $file->indirect_url }}" download
                    class="p-1.5 rounded-md text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    title="{{ trans('filament-media::media.download') }}">
                    <x-heroicon-m-arrow-down-tray class="w-4 h-4" />
                </a>
                @if ($deletable)
                    <button type="button"
                        x-on:click.prevent.stop="$wire.mountAction('trash', { items: [{ id: {{ $file->id }}, is_folder: false }] })"
                        class="p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                        title="{{ trans('filament-media::media.move_to_trash') }}">
                        <x-heroicon-m-trash class="w-4 h-4" />
                    </button>
                @endif
            </div>
        </div>
    @endforeach
</div>
