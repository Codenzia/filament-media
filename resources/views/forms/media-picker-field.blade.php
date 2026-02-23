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
    $disabled = $isDisabled();
    $effectiveExtensions = $getEffectiveExtensions();
    $extensionsSig = $getEffectiveExtensionsSignature();
    $displayStyle = $getDisplayStyle();
    $firstFile = $files->first();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            showPicker: false,
            showUploader: false,
            isUploading: false,
            isDragging: false,
            uploadProgress: 0,
            uploadError: null,
            previewUrl: null,
            directUpload: {{ $directUpload ? 'true' : 'false' }},
            state: $wire.$entangle('{{ $getStatePath() }}').live,
            fieldId: '{{ $fieldId }}',
            isMultiple: {{ $isMultiple ? 'true' : 'false' }},
            handleDrop(files) {
                this.isDragging = false;
                if (this.directUpload) {
                    this.handleDirectUpload(files);
                } else {
                    this.showPicker = true;
                }
            },
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
                    allowedTypes: @js($effectiveExtensions ?? FilamentMedia::getAllowedMimeTypesString()),
                    allowedTypesSig: @js($extensionsSig)
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
        {{-- ====================================================================
             COMPACT STYLE: Button + chip-style file list (original/default)
             ==================================================================== --}}
        @if($displayStyle === 'compact')
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
                                @unless($disabled)
                                    <button
                                        type="button"
                                        x-on:click="removeFile({{ $file->id }})"
                                        class="ml-1 p-0.5 rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                        title="{{ trans('filament-media::media.remove_file') }}"
                                    >
                                        <x-filament::icon icon="heroicon-m-x-mark" class="w-4 h-4" />
                                    </button>
                                @endunless
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 italic" x-show="!showUploader">{{ trans('filament-media::media.no_file_selected') }}</p>
            @endif

            {{-- Browse / Upload Links --}}
            @unless($disabled)
                <div class="flex items-center gap-2">
                    <button type="button" x-on:click="showPicker = true" class="text-sm text-primary-500 hover:text-primary-600 font-medium hover:underline cursor-pointer">
                        {{ trans('filament-media::media.browse_media') }}
                    </button>
                    @if($directUpload)
                        <span class="text-xs text-gray-400">{{ trans('filament-media::media.or') }}</span>
                        <button type="button" x-on:click="$refs.compactUploadInput.click()" class="text-sm text-primary-500 hover:text-primary-600 font-medium hover:underline cursor-pointer">
                            {{ trans('filament-media::media.upload_file') }}
                        </button>
                        <input
                            type="file"
                            class="hidden"
                            x-ref="compactUploadInput"
                            x-on:change="handleDirectUpload($event.target.files); $event.target.value = '';"
                            {{ $isMultiple ? 'multiple' : '' }}
                            @if(!empty($getAcceptedFileTypes()))
                                accept="{{ implode(',', $getAcceptedFileTypes()) }}"
                            @endif
                        />
                    @endif
                </div>
            @endunless

        {{-- ====================================================================
             DROPDOWN STYLE: Button with dropdown for browse/upload (old compact)
             ==================================================================== --}}
        @elseif($displayStyle === 'dropdown')
            {{-- Selected Files (same chip list as compact) --}}
            @if(!empty($fileIds))
                <div class="flex flex-wrap gap-2">
                    @foreach($fileIds as $fileId)
                        @php $file = $files->get($fileId); @endphp
                        @if($file)
                            <div class="group relative flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
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

                                @unless($disabled)
                                    <button
                                        type="button"
                                        x-on:click="removeFile({{ $file->id }})"
                                        class="ml-1 p-0.5 rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                        title="{{ trans('filament-media::media.remove_file') }}"
                                    >
                                        <x-filament::icon icon="heroicon-m-x-mark" class="w-4 h-4" />
                                    </button>
                                @endunless
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 italic" x-show="!showUploader">{{ trans('filament-media::media.no_file_selected') }}</p>
            @endif

            {{-- Dropdown Button --}}
            @unless($disabled)
                @include('filament-media::forms.partials.browse-button')
            @endunless

        {{-- ====================================================================
             THUMBNAIL STYLE: Visual preview card, click to browse
             ==================================================================== --}}
        @elseif($displayStyle === 'thumbnail')
            @if(!$isMultiple && $firstFile)
                {{-- Single file: large thumbnail card with drag-to-replace --}}
                <div class="group relative w-48">
                    <div
                        x-on:dragover.prevent="isDragging = true; $event.dataTransfer.dropEffect = 'copy'"
                        x-on:dragleave.prevent="isDragging = false"
                        x-on:drop.prevent="handleDrop($event.dataTransfer.files)"
                        :class="isDragging ? 'border-primary-500 ring-2 ring-primary-500/30' : 'border-gray-200 dark:border-gray-700'"
                        class="relative overflow-hidden rounded-lg border-2 bg-gray-100 dark:bg-gray-800 aspect-square transition-colors"
                    >
                        @if($firstFile->canGenerateThumbnails() && $urlService->fileExists($firstFile->url))
                            <img
                                src="{{ $firstFile->preview_url }}"
                                alt="{{ $firstFile->name }}"
                                class="w-full h-full object-contain cursor-pointer"
                                x-on:click="previewUrl = '{{ $firstFile->preview_url }}'"
                            />
                        @else
                            @php
                                $icon = match($firstFile->type) {
                                    'image' => 'heroicon-o-photo',
                                    'video' => 'heroicon-o-film',
                                    'audio' => 'heroicon-o-musical-note',
                                    default => 'heroicon-o-document',
                                };
                            @endphp
                            <div class="w-full h-full flex flex-col items-center justify-center gap-2">
                                <x-filament::icon :icon="$icon" class="w-12 h-12 text-gray-400 dark:text-gray-500" />
                                <span class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[160px] px-2">{{ $firstFile->name }}</span>
                            </div>
                        @endif

                        {{-- Upload progress overlay --}}
                        @include('filament-media::forms.partials.upload-progress-overlay')

                        {{-- Hover overlay with actions --}}
                        @unless($disabled)
                            <div x-show="!isUploading" class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                                <button
                                    type="button"
                                    x-on:click="showPicker = true"
                                    class="p-2 rounded-full bg-white/20 text-white hover:bg-white/30 transition-colors"
                                    title="{{ trans('filament-media::media.javascript.change_image') }}"
                                >
                                    <x-filament::icon icon="heroicon-m-arrow-path" class="w-5 h-5" />
                                </button>
                                <button
                                    type="button"
                                    x-on:click="removeFile({{ $firstFile->id }})"
                                    class="p-2 rounded-full bg-white/20 text-white hover:bg-red-500/80 transition-colors"
                                    title="{{ trans('filament-media::media.remove_file') }}"
                                >
                                    <x-filament::icon icon="heroicon-m-trash" class="w-5 h-5" />
                                </button>
                            </div>
                        @endunless
                    </div>
                </div>
            @elseif($isMultiple && !empty($fileIds))
                {{-- Multiple files: thumbnail grid --}}
                <div class="flex flex-wrap gap-3">
                    @foreach($fileIds as $fileId)
                        @php $file = $files->get($fileId); @endphp
                        @if($file)
                            <div class="group relative w-24">
                                <div class="relative overflow-hidden rounded-lg border-2 border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 aspect-square">
                                    @if($file->canGenerateThumbnails() && $urlService->fileExists($file->url))
                                        <img
                                            src="{{ $file->preview_url }}"
                                            alt="{{ $file->name }}"
                                            class="w-full h-full object-contain cursor-pointer"
                                            x-on:click="previewUrl = '{{ $file->preview_url }}'"
                                        />
                                    @else
                                        @php
                                            $icon = match($file->type) {
                                                'image' => 'heroicon-o-photo',
                                                'video' => 'heroicon-o-film',
                                                'audio' => 'heroicon-o-musical-note',
                                                default => 'heroicon-o-document',
                                            };
                                        @endphp
                                        <div class="w-full h-full flex items-center justify-center">
                                            <x-filament::icon :icon="$icon" class="w-8 h-8 text-gray-400 dark:text-gray-500" />
                                        </div>
                                    @endif

                                    {{-- Remove button overlay --}}
                                    @unless($disabled)
                                        <button
                                            type="button"
                                            x-on:click="removeFile({{ $file->id }})"
                                            class="absolute top-1 end-1 p-1 rounded-full bg-black/50 text-white opacity-0 group-hover:opacity-100 hover:bg-red-500/80 transition-all"
                                            title="{{ trans('filament-media::media.remove_file') }}"
                                        >
                                            <x-filament::icon icon="heroicon-m-x-mark" class="w-3.5 h-3.5" />
                                        </button>
                                    @endunless
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate text-center">{{ $file->name }}</p>
                            </div>
                        @endif
                    @endforeach

                    {{-- Add more button with drag-and-drop --}}
                    @unless($disabled)
                        <div class="w-24">
                            <div
                                x-on:click="showPicker = true"
                                x-on:dragover.prevent="isDragging = true; $event.dataTransfer.dropEffect = 'copy'"
                                x-on:dragleave.prevent="isDragging = false"
                                x-on:drop.prevent="handleDrop($event.dataTransfer.files)"
                                :class="isDragging ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50'"
                                class="relative w-full aspect-square rounded-lg border-2 border-dashed hover:border-primary-400 dark:hover:border-primary-500 flex items-center justify-center transition-colors cursor-pointer"
                            >
                                {{-- Upload progress overlay --}}
                                @include('filament-media::forms.partials.upload-progress-overlay')

                                <x-filament::icon icon="heroicon-m-plus" x-show="!isDragging && !isUploading" class="w-6 h-6 text-gray-400 dark:text-gray-500" />
                                <x-filament::icon icon="heroicon-o-cloud-arrow-up" x-show="isDragging && !isUploading" x-cloak class="w-6 h-6 text-primary-500" />
                            </div>
                        </div>
                    @endunless
                </div>
            @else
                {{-- Empty state: placeholder card with drag-and-drop --}}
                @unless($disabled)
                    <div
                        x-on:dragover.prevent="isDragging = true; $event.dataTransfer.dropEffect = 'copy'"
                        x-on:dragleave.prevent="isDragging = false"
                        x-on:drop.prevent="handleDrop($event.dataTransfer.files)"
                        :class="isDragging ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50'"
                        class="relative w-48 aspect-square rounded-lg border-2 border-dashed hover:border-primary-400 dark:hover:border-primary-500 flex flex-col items-center justify-center gap-2 transition-colors"
                    >
                        {{-- Upload progress overlay --}}
                        @include('filament-media::forms.partials.upload-progress-overlay')

                        {{-- Drag-over state --}}
                        <template x-if="isDragging && !isUploading">
                            <div class="flex flex-col items-center gap-2">
                                <x-filament::icon icon="heroicon-o-cloud-arrow-up" class="w-12 h-12 text-primary-500" />
                                <span class="text-sm text-primary-500 font-medium">
                                    {{ trans('filament-media::media.direct_upload_drop_short') }}
                                </span>
                            </div>
                        </template>

                        {{-- Default state --}}
                        <template x-if="!isDragging && !isUploading">
                            <div class="flex flex-col items-center gap-2">
                                <x-filament::icon icon="heroicon-o-photo" class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                                <div class="flex flex-col items-center gap-0.5">
                                    <button type="button" x-on:click="showPicker = true" class="text-sm text-primary-500 hover:text-primary-600 font-medium hover:underline cursor-pointer">
                                        {{ trans('filament-media::media.browse_media') }}
                                    </button>
                                    @if($directUpload)
                                        <span class="text-xs text-gray-400">{{ trans('filament-media::media.or') }}</span>
                                        <button type="button" x-on:click="$refs.thumbnailUploadInput.click()" class="text-sm text-primary-500 hover:text-primary-600 font-medium hover:underline cursor-pointer">
                                            {{ trans('filament-media::media.upload_file') }}
                                        </button>
                                        <input
                                            type="file"
                                            class="hidden"
                                            x-ref="thumbnailUploadInput"
                                            x-on:change="handleDirectUpload($event.target.files); $event.target.value = '';"
                                            {{ $isMultiple ? 'multiple' : '' }}
                                            @if(!empty($getAcceptedFileTypes()))
                                                accept="{{ implode(',', $getAcceptedFileTypes()) }}"
                                            @endif
                                        />
                                    @endif
                                </div>
                            </div>
                        </template>
                    </div>
                @else
                    <div class="w-48 aspect-square rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50 flex flex-col items-center justify-center gap-2">
                        <x-filament::icon icon="heroicon-o-photo" class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                        <span class="text-sm text-gray-400 italic">{{ trans('filament-media::media.no_file_selected') }}</span>
                    </div>
                @endunless
            @endif

        {{-- ====================================================================
             INTEGRATED LINKS STYLE: Thumbnail preview + text links below + drag & drop
             ==================================================================== --}}
        @elseif($displayStyle === 'integratedLinks')
            <div class="space-y-3">
                {{-- Thumbnail preview area with drag & drop --}}
                @if(!$isMultiple && $firstFile)
                    <div class="group relative w-48">
                        <div
                            x-on:dragover.prevent="isDragging = true; $event.dataTransfer.dropEffect = 'copy'"
                            x-on:dragleave.prevent="isDragging = false"
                            x-on:drop.prevent="handleDrop($event.dataTransfer.files)"
                            :class="isDragging ? 'border-primary-500 ring-2 ring-primary-500/30' : 'border-gray-200 dark:border-gray-700'"
                            class="relative overflow-hidden rounded-lg border-2 bg-gray-100 dark:bg-gray-800 aspect-square transition-colors"
                        >
                            @if($firstFile->canGenerateThumbnails() && $urlService->fileExists($firstFile->url))
                                <img
                                    src="{{ $firstFile->preview_url }}"
                                    alt="{{ $firstFile->name }}"
                                    class="w-full h-full object-contain cursor-pointer"
                                    x-on:click="previewUrl = '{{ $firstFile->preview_url }}'"
                                />
                            @else
                                @php
                                    $icon = match($firstFile->type) {
                                        'image' => 'heroicon-o-photo',
                                        'video' => 'heroicon-o-film',
                                        'audio' => 'heroicon-o-musical-note',
                                        default => 'heroicon-o-document',
                                    };
                                @endphp
                                <div class="w-full h-full flex flex-col items-center justify-center gap-2">
                                    <x-filament::icon :icon="$icon" class="w-12 h-12 text-gray-400 dark:text-gray-500" />
                                    <span class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[160px] px-2">{{ $firstFile->name }}</span>
                                </div>
                            @endif

                            {{-- Upload progress overlay --}}
                            @include('filament-media::forms.partials.upload-progress-overlay')

                            {{-- Remove overlay --}}
                            @unless($disabled)
                                <button
                                    type="button"
                                    x-show="!isUploading"
                                    x-on:click="removeFile({{ $firstFile->id }})"
                                    class="absolute top-2 end-2 p-1.5 rounded-full bg-black/50 text-white opacity-0 group-hover:opacity-100 hover:bg-red-500/80 transition-all"
                                    title="{{ trans('filament-media::media.remove_file') }}"
                                >
                                    <x-filament::icon icon="heroicon-m-x-mark" class="w-4 h-4" />
                                </button>
                            @endunless
                        </div>
                    </div>
                @elseif($isMultiple && !empty($fileIds))
                    {{-- Multiple files: thumbnail grid --}}
                    <div class="flex flex-wrap gap-3">
                        @foreach($fileIds as $fileId)
                            @php $file = $files->get($fileId); @endphp
                            @if($file)
                                <div class="group relative w-24">
                                    <div class="relative overflow-hidden rounded-lg border-2 border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 aspect-square">
                                        @if($file->canGenerateThumbnails() && $urlService->fileExists($file->url))
                                            <img
                                                src="{{ $file->preview_url }}"
                                                alt="{{ $file->name }}"
                                                class="w-full h-full object-contain cursor-pointer"
                                                x-on:click="previewUrl = '{{ $file->preview_url }}'"
                                            />
                                        @else
                                            @php
                                                $icon = match($file->type) {
                                                    'image' => 'heroicon-o-photo',
                                                    'video' => 'heroicon-o-film',
                                                    'audio' => 'heroicon-o-musical-note',
                                                    default => 'heroicon-o-document',
                                                };
                                            @endphp
                                            <div class="w-full h-full flex items-center justify-center">
                                                <x-filament::icon :icon="$icon" class="w-8 h-8 text-gray-400 dark:text-gray-500" />
                                            </div>
                                        @endif

                                        {{-- Remove button overlay --}}
                                        @unless($disabled)
                                            <button
                                                type="button"
                                                x-on:click="removeFile({{ $file->id }})"
                                                class="absolute top-1 end-1 p-1 rounded-full bg-black/50 text-white opacity-0 group-hover:opacity-100 hover:bg-red-500/80 transition-all"
                                                title="{{ trans('filament-media::media.remove_file') }}"
                                            >
                                                <x-filament::icon icon="heroicon-m-x-mark" class="w-3.5 h-3.5" />
                                            </button>
                                        @endunless
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate text-center">{{ $file->name }}</p>
                                </div>
                            @endif
                        @endforeach

                        {{-- Add more button with drag-and-drop --}}
                        @unless($disabled)
                            <div class="w-24">
                                <div
                                    x-on:click="showPicker = true"
                                    x-on:dragover.prevent="isDragging = true; $event.dataTransfer.dropEffect = 'copy'"
                                    x-on:dragleave.prevent="isDragging = false"
                                    x-on:drop.prevent="handleDrop($event.dataTransfer.files)"
                                    :class="isDragging ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50'"
                                    class="relative w-full aspect-square rounded-lg border-2 border-dashed hover:border-primary-400 dark:hover:border-primary-500 flex items-center justify-center transition-colors cursor-pointer"
                                >
                                    {{-- Upload progress overlay --}}
                                    @include('filament-media::forms.partials.upload-progress-overlay')

                                    <x-filament::icon icon="heroicon-m-plus" x-show="!isDragging && !isUploading" class="w-6 h-6 text-gray-400 dark:text-gray-500" />
                                    <x-filament::icon icon="heroicon-o-cloud-arrow-up" x-show="isDragging && !isUploading" x-cloak class="w-6 h-6 text-primary-500" />
                                </div>
                            </div>
                        @endunless
                    </div>
                @else
                    {{-- Empty state: placeholder with drag-and-drop --}}
                    @unless($disabled)
                        <div
                            x-on:dragover.prevent="isDragging = true; $event.dataTransfer.dropEffect = 'copy'"
                            x-on:dragleave.prevent="isDragging = false"
                            x-on:drop.prevent="handleDrop($event.dataTransfer.files)"
                            :class="isDragging ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50'"
                            class="relative w-48 aspect-square rounded-lg border-2 border-dashed hover:border-primary-400 dark:hover:border-primary-500 flex flex-col items-center justify-center gap-2 transition-colors"
                        >
                            {{-- Upload progress overlay --}}
                            @include('filament-media::forms.partials.upload-progress-overlay')

                            {{-- Drag-over state --}}
                            <template x-if="isDragging && !isUploading">
                                <div class="flex flex-col items-center gap-2">
                                    <x-filament::icon icon="heroicon-o-cloud-arrow-up" class="w-12 h-12 text-primary-500" />
                                    <span class="text-sm text-primary-500 font-medium">
                                        {{ trans('filament-media::media.direct_upload_drop_short') }}
                                    </span>
                                </div>
                            </template>

                            {{-- Default state --}}
                            <template x-if="!isDragging && !isUploading">
                                <div class="flex flex-col items-center gap-2">
                                    <x-filament::icon icon="heroicon-o-photo" class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                                    <span class="text-sm text-gray-400 italic">{{ trans('filament-media::media.no_file_selected') }}</span>
                                </div>
                            </template>
                        </div>
                    @else
                        <div class="w-48 aspect-square rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50 flex flex-col items-center justify-center gap-2">
                            <x-filament::icon icon="heroicon-o-photo" class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                            <span class="text-sm text-gray-400 italic">{{ trans('filament-media::media.no_file_selected') }}</span>
                        </div>
                    @endunless
                @endif

                {{-- Text links below --}}
                @unless($disabled)
                    <div class="flex items-center gap-2">
                        <button type="button" x-on:click="showPicker = true" class="text-sm text-primary-500 hover:text-primary-600 font-medium hover:underline cursor-pointer">
                            {{ trans('filament-media::media.browse_media') }}
                        </button>
                        @if($directUpload)
                            <span class="text-xs text-gray-400">{{ trans('filament-media::media.or') }}</span>
                            <button type="button" x-on:click="$refs.intLinksUploadInput.click()" class="text-sm text-primary-500 hover:text-primary-600 font-medium hover:underline cursor-pointer">
                                {{ trans('filament-media::media.upload_file') }}
                            </button>
                            <input
                                type="file"
                                class="hidden"
                                x-ref="intLinksUploadInput"
                                x-on:change="handleDirectUpload($event.target.files); $event.target.value = '';"
                                {{ $isMultiple ? 'multiple' : '' }}
                                @if(!empty($getAcceptedFileTypes()))
                                    accept="{{ implode(',', $getAcceptedFileTypes()) }}"
                                @endif
                            />
                        @endif
                    </div>
                @endunless
            </div>

        {{-- ====================================================================
             INTEGRATED DROPDOWN STYLE: Thumbnail preview + dropdown button below + drag & drop
             ==================================================================== --}}
        @elseif($displayStyle === 'integratedDropdown')
            <div class="space-y-3">
                {{-- Thumbnail preview area with drag & drop --}}
                @if(!$isMultiple && $firstFile)
                    <div class="group relative w-48">
                        <div
                            x-on:dragover.prevent="isDragging = true; $event.dataTransfer.dropEffect = 'copy'"
                            x-on:dragleave.prevent="isDragging = false"
                            x-on:drop.prevent="handleDrop($event.dataTransfer.files)"
                            :class="isDragging ? 'border-primary-500 ring-2 ring-primary-500/30' : 'border-gray-200 dark:border-gray-700'"
                            class="relative overflow-hidden rounded-lg border-2 bg-gray-100 dark:bg-gray-800 aspect-square transition-colors"
                        >
                            @if($firstFile->canGenerateThumbnails() && $urlService->fileExists($firstFile->url))
                                <img
                                    src="{{ $firstFile->preview_url }}"
                                    alt="{{ $firstFile->name }}"
                                    class="w-full h-full object-contain cursor-pointer"
                                    x-on:click="previewUrl = '{{ $firstFile->preview_url }}'"
                                />
                            @else
                                @php
                                    $icon = match($firstFile->type) {
                                        'image' => 'heroicon-o-photo',
                                        'video' => 'heroicon-o-film',
                                        'audio' => 'heroicon-o-musical-note',
                                        default => 'heroicon-o-document',
                                    };
                                @endphp
                                <div class="w-full h-full flex flex-col items-center justify-center gap-2">
                                    <x-filament::icon :icon="$icon" class="w-12 h-12 text-gray-400 dark:text-gray-500" />
                                    <span class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[160px] px-2">{{ $firstFile->name }}</span>
                                </div>
                            @endif

                            {{-- Upload progress overlay --}}
                            @include('filament-media::forms.partials.upload-progress-overlay')

                            {{-- Remove overlay --}}
                            @unless($disabled)
                                <button
                                    type="button"
                                    x-show="!isUploading"
                                    x-on:click="removeFile({{ $firstFile->id }})"
                                    class="absolute top-2 end-2 p-1.5 rounded-full bg-black/50 text-white opacity-0 group-hover:opacity-100 hover:bg-red-500/80 transition-all"
                                    title="{{ trans('filament-media::media.remove_file') }}"
                                >
                                    <x-filament::icon icon="heroicon-m-x-mark" class="w-4 h-4" />
                                </button>
                            @endunless
                        </div>
                    </div>
                @elseif($isMultiple && !empty($fileIds))
                    {{-- Multiple files: thumbnail grid --}}
                    <div class="flex flex-wrap gap-3">
                        @foreach($fileIds as $fileId)
                            @php $file = $files->get($fileId); @endphp
                            @if($file)
                                <div class="group relative w-24">
                                    <div class="relative overflow-hidden rounded-lg border-2 border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 aspect-square">
                                        @if($file->canGenerateThumbnails() && $urlService->fileExists($file->url))
                                            <img
                                                src="{{ $file->preview_url }}"
                                                alt="{{ $file->name }}"
                                                class="w-full h-full object-contain cursor-pointer"
                                                x-on:click="previewUrl = '{{ $file->preview_url }}'"
                                            />
                                        @else
                                            @php
                                                $icon = match($file->type) {
                                                    'image' => 'heroicon-o-photo',
                                                    'video' => 'heroicon-o-film',
                                                    'audio' => 'heroicon-o-musical-note',
                                                    default => 'heroicon-o-document',
                                                };
                                            @endphp
                                            <div class="w-full h-full flex items-center justify-center">
                                                <x-filament::icon :icon="$icon" class="w-8 h-8 text-gray-400 dark:text-gray-500" />
                                            </div>
                                        @endif

                                        {{-- Remove button overlay --}}
                                        @unless($disabled)
                                            <button
                                                type="button"
                                                x-on:click="removeFile({{ $file->id }})"
                                                class="absolute top-1 end-1 p-1 rounded-full bg-black/50 text-white opacity-0 group-hover:opacity-100 hover:bg-red-500/80 transition-all"
                                                title="{{ trans('filament-media::media.remove_file') }}"
                                            >
                                                <x-filament::icon icon="heroicon-m-x-mark" class="w-3.5 h-3.5" />
                                            </button>
                                        @endunless
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate text-center">{{ $file->name }}</p>
                                </div>
                            @endif
                        @endforeach

                        {{-- Add more button with drag-and-drop --}}
                        @unless($disabled)
                            <div class="w-24">
                                <div
                                    x-on:click="showPicker = true"
                                    x-on:dragover.prevent="isDragging = true; $event.dataTransfer.dropEffect = 'copy'"
                                    x-on:dragleave.prevent="isDragging = false"
                                    x-on:drop.prevent="handleDrop($event.dataTransfer.files)"
                                    :class="isDragging ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50'"
                                    class="relative w-full aspect-square rounded-lg border-2 border-dashed hover:border-primary-400 dark:hover:border-primary-500 flex items-center justify-center transition-colors cursor-pointer"
                                >
                                    {{-- Upload progress overlay --}}
                                    @include('filament-media::forms.partials.upload-progress-overlay')

                                    <x-filament::icon icon="heroicon-m-plus" x-show="!isDragging && !isUploading" class="w-6 h-6 text-gray-400 dark:text-gray-500" />
                                    <x-filament::icon icon="heroicon-o-cloud-arrow-up" x-show="isDragging && !isUploading" x-cloak class="w-6 h-6 text-primary-500" />
                                </div>
                            </div>
                        @endunless
                    </div>
                @else
                    {{-- Empty state: placeholder with drag-and-drop --}}
                    @unless($disabled)
                        <div
                            x-on:dragover.prevent="isDragging = true; $event.dataTransfer.dropEffect = 'copy'"
                            x-on:dragleave.prevent="isDragging = false"
                            x-on:drop.prevent="handleDrop($event.dataTransfer.files)"
                            :class="isDragging ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50'"
                            class="relative w-48 aspect-square rounded-lg border-2 border-dashed hover:border-primary-400 dark:hover:border-primary-500 flex flex-col items-center justify-center gap-2 transition-colors"
                        >
                            {{-- Upload progress overlay --}}
                            @include('filament-media::forms.partials.upload-progress-overlay')

                            {{-- Drag-over state --}}
                            <template x-if="isDragging && !isUploading">
                                <div class="flex flex-col items-center gap-2">
                                    <x-filament::icon icon="heroicon-o-cloud-arrow-up" class="w-12 h-12 text-primary-500" />
                                    <span class="text-sm text-primary-500 font-medium">
                                        {{ trans('filament-media::media.direct_upload_drop_short') }}
                                    </span>
                                </div>
                            </template>

                            {{-- Default state --}}
                            <template x-if="!isDragging && !isUploading">
                                <div class="flex flex-col items-center gap-2">
                                    <x-filament::icon icon="heroicon-o-photo" class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                                    <span class="text-sm text-gray-400 italic">{{ trans('filament-media::media.no_file_selected') }}</span>
                                </div>
                            </template>
                        </div>
                    @else
                        <div class="w-48 aspect-square rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50 flex flex-col items-center justify-center gap-2">
                            <x-filament::icon icon="heroicon-o-photo" class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                            <span class="text-sm text-gray-400 italic">{{ trans('filament-media::media.no_file_selected') }}</span>
                        </div>
                    @endunless
                @endif

                {{-- Dropdown button below --}}
                @unless($disabled)
                    @include('filament-media::forms.partials.browse-button')
                @endunless
            </div>
        @endif

        {{-- ====================================================================
             SHARED: Direct upload zone (all styles)
             ==================================================================== --}}
        @unless($disabled)
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
        @endunless

        {{-- ====================================================================
             SHARED: Image Preview Lightbox
             ==================================================================== --}}
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

        {{-- ====================================================================
             SHARED: Picker Modal Overlay
             ==================================================================== --}}
        @unless($disabled)
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
                    'allowedExtensions' => $effectiveExtensions,
                    'allowedExtensionsSig' => $extensionsSig,
                ], key('media-picker-' . $fieldId))
            </div>
        </div>
        @endunless
    </div>
</x-dynamic-component>
