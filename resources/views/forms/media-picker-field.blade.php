@php
    use Codenzia\FilamentMedia\Models\MediaFile;
    use Codenzia\FilamentMedia\Services\MediaUrlService;
    use Codenzia\FilamentMedia\Facades\FilamentMedia;

    $fieldId = $getId();
    $isMultiple = $isMultiple();
    $state = $getState();
    $fileIds = is_array($state) ? $state : ($state ? [$state] : []);
    $files = MediaFile::withoutGlobalScopes()->whereIn('id', $fileIds)->get()->keyBy('id');
    $urlService = app(MediaUrlService::class);
    $directUpload = $isDirectUploadEnabled();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            showPicker: false,
            showUploader: false,
            isUploading: false,
            uploadProgress: 0,
            uploadError: null,
            previewUrl: null,
            state: $wire.$entangle('{{ $getStatePath() }}').live,
            fieldId: '{{ $fieldId }}',
            isMultiple: {{ $isMultiple ? 'true' : 'false' }},
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
            },
            handleDirectUpload(files) {
                if (!files || !files.length) return;

                const uploader = new window.FilamentMedia.UploadService({
                    uploadUrl: '{{ $getUploadUrl() }}',
                    maxFileSize: {{ $getMaxUploadSize() }},
                    allowedTypes: @js(FilamentMedia::getAllowedMimeTypesString())
                });

                this.isUploading = true;
                this.uploadProgress = 0;
                this.uploadError = null;

                const filesToUpload = this.isMultiple ? Array.from(files) : [files[0]];
                let completedCount = 0;

                filesToUpload.forEach((file) => {
                    uploader.upload(file, 0, {
                        onProgress: (percent) => {
                            this.uploadProgress = percent;
                        },
                        onComplete: (response) => {
                            completedCount++;
                            const fileId = response?.data?.id || response?.id;
                            if (fileId) {
                                if (this.isMultiple) {
                                    this.state = Array.isArray(this.state) ? [...this.state, fileId] : [fileId];
                                } else {
                                    this.state = fileId;
                                }
                            }
                            if (completedCount >= filesToUpload.length) {
                                this.isUploading = false;
                                this.showUploader = false;
                                this.uploadProgress = 0;
                            }
                        },
                        onError: (message) => {
                            completedCount++;
                            this.uploadError = message;
                            if (completedCount >= filesToUpload.length) {
                                this.isUploading = false;
                            }
                        }
                    });
                });
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
                            {{-- Thumbnail or Icon (clickable for preview) --}}
                            @if($file->canGenerateThumbnails() && $urlService->fileExists($file->url))
                                <button type="button" x-on:click="previewUrl = '{{ $file->preview_url }}'" class="cursor-pointer shrink-0">
                                    <img src="{{ $file->preview_url }}" alt="{{ $file->name }}" class="max-w-8 max-h-8 rounded object-contain hover:ring-2 hover:ring-primary-500 transition-shadow" />
                                </button>
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
            <p class="text-sm text-gray-400 italic" x-show="!showUploader">{{ trans('filament-media::media.no_file_selected') }}</p>
        @endif

        {{-- Browse / Upload Buttons --}}
        @if($directUpload)
            <x-filament::dropdown placement="bottom-start" teleport>
                <x-slot name="trigger">
                    <x-filament::button type="button" color="gray" icon="heroicon-m-photo" icon-position="before">
                        {{ trans('filament-media::media.browse_media') }}
                        <x-filament::icon icon="heroicon-m-chevron-down" class="w-4 h-4 ms-1" />
                    </x-filament::button>
                </x-slot>

                <x-filament::dropdown.list>
                    <x-filament::dropdown.list.item icon="heroicon-m-photo" x-on:click="showPicker = true">
                        {{ trans('filament-media::media.browse_media') }}
                    </x-filament::dropdown.list.item>

                    <x-filament::dropdown.list.item icon="heroicon-m-arrow-up-tray" x-on:click="showUploader = true; uploadError = null;">
                        {{ trans('filament-media::media.upload_file') }}
                    </x-filament::dropdown.list.item>
                </x-filament::dropdown.list>
            </x-filament::dropdown>
        @else
            <x-filament::button
                type="button"
                color="gray"
                icon="heroicon-m-photo"
                x-on:click="showPicker = true"
            >
                {{ trans('filament-media::media.browse_media') }}
            </x-filament::button>
        @endif

        {{-- Inline Upload Zone --}}
        <div
            x-show="showUploader"
            x-cloak
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="relative"
        >
            {{-- Upload Drop Zone --}}
            <div
                x-show="!isUploading"
                x-on:dragover.prevent="$event.dataTransfer.dropEffect = 'copy'"
                x-on:drop.prevent="handleDirectUpload($event.dataTransfer.files)"
                class="flex flex-col items-center gap-2 p-6 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-primary-400 dark:hover:border-primary-500 transition-colors bg-gray-50 dark:bg-gray-800/50"
                x-on:click="$refs.directUploadInput.click()"
            >
                <x-filament::icon icon="heroicon-o-cloud-arrow-up" class="w-8 h-8 text-gray-400" />
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ trans('filament-media::media.direct_upload_drop') }}
                </p>
                <input
                    type="file"
                    class="hidden"
                    x-ref="directUploadInput"
                    x-on:change="handleDirectUpload($event.target.files); $event.target.value = '';"
                    {{ $isMultiple ? 'multiple' : '' }}
                    @if(!empty($getAcceptedFileTypes()))
                        accept="{{ implode(',', $getAcceptedFileTypes()) }}"
                    @endif
                />
            </div>

            {{-- Upload Progress --}}
            <div x-show="isUploading" class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800">
                <div class="flex items-center gap-3">
                    <svg class="animate-spin h-5 w-5 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <div class="flex-1">
                        <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                            <span>{{ trans('filament-media::media.upload_progress') }}</span>
                            <span x-text="uploadProgress + '%'"></span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-primary-500 h-2 rounded-full transition-all duration-300" :style="'width: ' + uploadProgress + '%'"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Upload Error --}}
            <div x-show="uploadError" x-cloak class="mt-2 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                <p class="text-sm text-red-600 dark:text-red-400" x-text="uploadError"></p>
            </div>

            {{-- Close Upload Zone --}}
            <button
                type="button"
                x-on:click="showUploader = false; uploadError = null;"
                x-show="!isUploading"
                class="absolute top-2 right-2 p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
            >
                <x-filament::icon icon="heroicon-m-x-mark" class="w-4 h-4" />
            </button>
        </div>

        {{-- Image Preview Lightbox --}}
        <div
            x-show="previewUrl"
            x-cloak
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-black/80"
            x-on:click.self="previewUrl = null"
            x-on:keydown.escape.window="previewUrl = null"
        >
            <button type="button" x-on:click="previewUrl = null"
                class="absolute top-4 right-4 z-10 p-2 rounded-full bg-black/50 text-white hover:bg-black/70 transition-colors">
                <x-filament::icon icon="heroicon-m-x-mark" class="w-6 h-6" />
            </button>
            <img :src="previewUrl" alt="Preview" class="max-w-full max-h-full rounded-lg shadow-2xl object-contain" />
        </div>

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
