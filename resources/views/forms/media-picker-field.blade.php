@php
    use Codenzia\FilamentMedia\Models\MediaFile;
    use Codenzia\FilamentMedia\Services\MediaUrlService;

    $fieldId = $getId();
    $isMultiple = $isMultiple();
    $state = $getState();
    $fileIds = is_array($state) ? $state : ($state ? [$state] : []);
    $files = MediaFile::whereIn('id', $fileIds)->get()->keyBy('id');
    $urlService = app(MediaUrlService::class);
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            showPicker: false,
            state: $wire.$entangle('{{ $getStatePath() }}'),
            fieldId: '{{ $fieldId }}',
            init() {
                window.addEventListener('media-picker-selected', (e) => {
                    if (e.detail.fieldId === this.fieldId) {
                        this.state = e.detail.value;
                        this.showPicker = false;
                    }
                });
                window.addEventListener('media-picker-cancelled', (e) => {
                    if (e.detail.fieldId === this.fieldId) {
                        this.showPicker = false;
                    }
                });
            },
            removeFile(id) {
                if (Array.isArray(this.state)) {
                    this.state = this.state.filter(v => v !== id);
                } else {
                    this.state = null;
                }
            }
        }"
        class="space-y-3"
    >
        {{-- Selected Files --}}
        @if(!empty($fileIds))
            <div class="flex flex-wrap gap-2">
                @foreach($fileIds as $fileId)
                    @php $file = $files->get($fileId); @endphp
                    @if($file)
                        <div class="group relative flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                            {{-- Thumbnail or Icon --}}
                            @if($file->canGenerateThumbnails() && $urlService->fileExists($file->url))
                                <img src="{{ $file->url }}" alt="{{ $file->name }}" class="w-8 h-8 rounded object-cover" />
                            @else
                                @php
                                    $icon = match($file->type) {
                                        'image' => 'heroicon-o-photo',
                                        'video' => 'heroicon-o-film',
                                        'audio' => 'heroicon-o-musical-note',
                                        default => 'heroicon-o-document',
                                    };
                                @endphp
                                <div class="w-8 h-8 rounded bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                    <x-filament::icon :icon="$icon" class="w-4 h-4 text-gray-900 dark:text-gray-400" />
                                </div>
                            @endif

                            <span class="text-sm text-gray-700 dark:text-gray-300 truncate max-w-[200px]">
                                {{ $file->name }}
                            </span>

                            {{-- Remove Button --}}
                            <button
                                type="button"
                                x-on:click="removeFile({{ $file->id }})"
                                class="ml-1 p-0.5 rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                title="{{ trans('filament-media::media.remove_file') }}"
                            >
                                <x-filament::icon icon="heroicon-m-x-mark" class="w-4 h-4" />
                            </button>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400 italic">{{ trans('filament-media::media.no_file_selected') }}</p>
        @endif

        {{-- Browse Button --}}
        <x-filament::button
            type="button"
            color="gray"
            icon="heroicon-m-photo"
            x-on:click="showPicker = true"
        >
            {{ trans('filament-media::media.browse_media') }}
        </x-filament::button>

        {{-- Picker Modal Overlay --}}
        <div
            x-show="showPicker"
            x-cloak
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-black/50"
            x-on:keydown.escape.window="showPicker = false"
        >
            <div
                x-show="showPicker"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="w-full max-w-4xl rounded-xl shadow-2xl overflow-hidden"
                x-on:click.outside="showPicker = false"
            >
                @livewire('filament-media::media-picker', [
                    'multiple' => $isMultiple,
                    'acceptedFileTypes' => $getAcceptedFileTypes(),
                    'maxFiles' => $getMaxFiles(),
                    'collection' => $getCollection(),
                    'directory' => $getDirectory(),
                    'fieldId' => $fieldId,
                ], key('media-picker-' . $fieldId))
            </div>
        </div>
    </div>
</x-dynamic-component>
