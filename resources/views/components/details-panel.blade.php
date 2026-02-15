@php
    $fileExists = $details['file_exists'] ?? true;
@endphp

{{-- Details Panel Content --}}
<div class="flex flex-col h-full overflow-hidden">
    {{-- Preview Area --}}
    <div class="flex-shrink-0 p-3 border-b border-gray-200 dark:border-gray-700">
        <div
            class="h-48 max-h-48 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800 flex items-center justify-center relative">
            @if ($details['type'] === 'folder')
                <x-filament::icon icon="heroicon-s-folder"
                    class="w-24 h-24 {{ isset($details['color']) && $details['color'] ? '' : 'text-amber-500' }}"
                    style="{{ isset($details['color']) && $details['color'] ? 'color: ' . $details['color'] : '' }}" />
            @elseif(!$fileExists)
                @include('filament-media::components.missing-file')
            @elseif($details['thumbnail'])
                <img src="{{ $details['thumbnail'] }}" alt="{{ $details['name'] }}"
                    class="max-w-full max-h-48 object-contain cursor-pointer"
                    wire:click="openItem({{ Js::from(['id' => $details['id'], 'is_folder' => false]) }})"
                    title="{{ trans('filament-media::media.preview') }}" />
            @else
                @php
                    $iconColor = match ($details['file_type'] ?? 'document') {
                        'image' => 'text-blue-500',
                        'video' => 'text-purple-500',
                        'audio' => 'text-pink-500',
                        'document' => 'text-red-500',
                        default => 'text-gray-900 dark:text-gray-400',
                    };
                    $icon = match ($details['file_type'] ?? 'document') {
                        'image' => 'heroicon-o-photo',
                        'video' => 'heroicon-o-film',
                        'audio' => 'heroicon-o-musical-note',
                        'document' => 'heroicon-o-document-text',
                        default => 'heroicon-o-document',
                    };
                @endphp
                <x-filament::icon :icon="$icon" class="w-16 h-16 {{ $iconColor }}" />
            @endif
        </div>
    </div>

    {{-- Details Content --}}
    <div class="flex-1 overflow-y-auto p-4">
        {{-- Name --}}
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white break-words mb-2">
            {{ $details['name'] }}
        </h3>

        @if ($details['type'] === 'file' && isset($details['visibility']))
            <div class="mb-4 flex items-center gap-2">
                @if (($details['visibility'] ?? 'public') === 'private')
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                        <x-filament::icon icon="heroicon-m-lock-closed" class="w-3 h-3" />
                        {{ trans('filament-media::media.visibility_private') }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                        <x-filament::icon icon="heroicon-m-globe-alt" class="w-3 h-3" />
                        {{ trans('filament-media::media.visibility_public') }}
                    </span>
                @endif
            </div>
        @endif

        {{-- Quick Actions --}}
        <div class="flex flex-wrap gap-2 mb-6">
            @if ($details['type'] === 'file')
                @if ($fileExists)
                    <x-filament::button size="sm" color="gray" icon="heroicon-m-eye"
                        wire:click="openItem({{ Js::from(['id' => $details['id'], 'is_folder' => false]) }})">
                        {{ trans('filament-media::media.preview') }}
                    </x-filament::button>

                    <x-filament::button size="sm" color="gray" icon="heroicon-m-arrow-down-tray" tag="a"
                        :href="$details['url']" target="_blank" download>
                        {{ trans('filament-media::media.download') }}
                    </x-filament::button>

                    <x-filament::button size="sm" color="gray" icon="heroicon-m-link"
                        x-on:click="window.FilamentMedia.download.copyToClipboard('{{ $details['url'] }}').then(() => { $dispatch('notify', { status: 'success', message: '{{ trans('filament-media::media.link_copied') }}' }) })">
                        {{ trans('filament-media::media.copy_link') }}
                    </x-filament::button>
                @else
                    {{-- Orphaned file warning --}}
                    <div
                        class="w-full p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                        <p class="text-sm text-red-700 dark:text-red-400">
                            {{ trans('filament-media::media.file_missing_description') }}
                        </p>
                    </div>
                @endif
            @else
                <x-filament::button size="sm" color="gray" icon="heroicon-m-folder-open"
                    wire:click="navigateToFolder({{ $details['id'] }})">
                    {{ trans('filament-media::media.open_folder') }}
                </x-filament::button>
            @endif
        </div>

        {{-- Metadata --}}
        <div class="space-y-4">
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                {{ trans('filament-media::media.details') }}
            </h4>

            <dl class="space-y-3">
                @if ($details['type'] === 'file')
                    {{-- File Size --}}
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ trans('filament-media::media.size') }}
                        </dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $details['size'] }}</dd>
                    </div>

                    {{-- MIME Type --}}
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ trans('filament-media::media.type') }}
                        </dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $details['mime_type'] }}</dd>
                    </div>
                @else
                    {{-- Folder Contents --}}
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                            {{ trans('filament-media::media.contents') }}</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $details['files_count'] }} {{ trans('filament-media::media.files') }},
                            {{ $details['folders_count'] }} {{ trans('filament-media::media.folders') }}
                        </dd>
                    </div>
                @endif

                {{-- Created At --}}
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ trans('filament-media::media.created') }}
                    </dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $details['created_at'] }}</dd>
                </div>

                {{-- Updated At --}}
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ trans('filament-media::media.modified') }}
                    </dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $details['updated_at'] }}</dd>
                </div>

                @if ($details['type'] === 'file' && isset($details['alt']))
                    {{-- Alt Text --}}
                    <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                        <dt class="text-sm text-gray-500 dark:text-gray-400 mb-1">
                            {{ trans('filament-media::media.alt_text') }}</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">
                            @if ($details['alt'])
                                {{ $details['alt'] }}
                            @else
                                <span
                                    class="text-gray-400 italic">{{ trans('filament-media::media.no_alt_text') }}</span>
                            @endif
                        </dd>
                    </div>
                @endif

                @php
                    $itemData = [['id' => $details['id'], 'is_folder' => $details['type'] === 'folder']];
                @endphp

                @if ($details['type'] === 'file')
                    {{-- Linked Model --}}
                    <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                        <dt class="text-sm text-gray-500 dark:text-gray-400 mb-1">
                            {{ trans('filament-media::media.linked_to') }}
                        </dt>
                        <dd class="text-sm text-gray-900 dark:text-white">
                            @if ($details['linked_model'] ?? null)
                                <button
                                    type="button"
                                    class="text-primary-600 dark:text-primary-400 hover:underline inline-flex items-center gap-1"
                                    wire:click="openParentDetailsModal({{ Js::from($itemData) }})"
                                >
                                    {{ $details['linked_model']['label'] }}
                                    <x-filament::icon icon="heroicon-m-information-circle" class="w-3.5 h-3.5" />
                                </button>
                            @else
                                <span class="text-gray-400 italic">{{ trans('filament-media::media.not_linked') }}</span>
                            @endif
                        </dd>
                    </div>
                @endif

                @if ($details['type'] === 'file')
                    {{-- Tags --}}
                    @if(config('media.features.tags', true))
                        <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between mb-1">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ trans('filament-media::media.tags') }}
                                </dt>
                                <button
                                    type="button"
                                    class="text-xs text-primary-600 dark:text-primary-400 hover:underline"
                                    wire:click="openTagModal({{ Js::from($itemData) }})"
                                >
                                    {{ trans('filament-media::media.edit') }}
                                </button>
                            </div>
                            <dd class="flex flex-wrap gap-1">
                                @if (!empty($details['tags']))
                                    @foreach ($details['tags'] as $tag)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-400">
                                            {{ $tag }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-sm text-gray-400 italic">{{ trans('filament-media::media.no_tags') }}</span>
                                @endif
                            </dd>
                        </div>
                    @endif

                    {{-- Collections --}}
                    @if(config('media.features.collections', true))
                        <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between mb-1">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ trans('filament-media::media.collections') }}
                                </dt>
                                <button
                                    type="button"
                                    class="text-xs text-primary-600 dark:text-primary-400 hover:underline"
                                    wire:click="openCollectionModal({{ Js::from($itemData) }})"
                                >
                                    {{ trans('filament-media::media.add') }}
                                </button>
                            </div>
                            <dd class="flex flex-wrap gap-1">
                                @if (!empty($details['collections']))
                                    @foreach ($details['collections'] as $collection)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            {{ $collection }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-sm text-gray-400 italic">{{ trans('filament-media::media.no_collections') }}</span>
                                @endif
                            </dd>
                        </div>
                    @endif

                    {{-- Version Info --}}
                    @if(config('media.features.versioning', true))
                        <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between mb-1">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ trans('filament-media::media.versions') }}
                                </dt>
                                <button
                                    type="button"
                                    class="text-xs text-primary-600 dark:text-primary-400 hover:underline"
                                    wire:click="openVersionModal({{ Js::from($itemData) }})"
                                >
                                    {{ trans('filament-media::media.upload_new') }}
                                </button>
                            </div>
                            <dd class="text-sm text-gray-900 dark:text-white">
                                @if (($details['version_count'] ?? 0) > 0)
                                    {{ trans('filament-media::media.version_count', ['count' => $details['version_count']]) }}
                                    @if ($details['latest_version'] ?? null)
                                        <span class="text-gray-400">
                                            &middot; v{{ $details['latest_version']['version_number'] }}
                                            ({{ $details['latest_version']['created_at'] }})
                                        </span>
                                    @endif
                                @else
                                    <span class="text-gray-400 italic">{{ trans('filament-media::media.no_versions') }}</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                @endif
            </dl>
        </div>
    </div>

    {{-- Actions Footer --}}
    <div class="flex-shrink-0 p-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
        <div class="flex flex-wrap gap-2">
            <x-filament::button size="sm" color="gray" icon="heroicon-m-pencil"
                wire:click="openRenameModal({{ Js::from($itemData) }})">
                {{ trans('filament-media::media.rename') }}
            </x-filament::button>

            @if ($details['type'] === 'folder')
                <x-filament::button size="sm" color="gray" icon="heroicon-m-swatch"
                    wire:click="openPropertiesModal({{ Js::from($itemData) }})">
                    {{ trans('filament-media::media.properties.name') }}
                </x-filament::button>
            @else
                <x-filament::button size="sm" color="gray" icon="heroicon-m-chat-bubble-left"
                    wire:click="openAltTextModal({{ Js::from($itemData) }})">
                    {{ trans('filament-media::media.alt_text') }}
                </x-filament::button>

                @if(config('media.features.tags', true))
                    <x-filament::button size="sm" color="gray" icon="heroicon-m-tag"
                        wire:click="openTagModal({{ Js::from($itemData) }})">
                        {{ trans('filament-media::media.tags') }}
                    </x-filament::button>
                @endif

                @if(config('media.features.metadata', true))
                    <x-filament::button size="sm" color="gray" icon="heroicon-m-document-text"
                        wire:click="openMetadataModal({{ Js::from($itemData) }})">
                        {{ trans('filament-media::media.edit_metadata') }}
                    </x-filament::button>
                @endif
            @endif

            <x-filament::button size="sm" color="danger" icon="heroicon-m-trash"
                wire:click="openTrashModal({{ Js::from($itemData) }})">
                {{ trans('filament-media::media.delete') }}
            </x-filament::button>
        </div>
    </div>
</div>
