<?php

namespace Codenzia\FilamentMedia\Pages;

use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Codenzia\FilamentMedia\Helpers\BaseHelper;
use Codenzia\FilamentMedia\Models\MediaSetting;
use Codenzia\FilamentMedia\Services\OrphanScanService;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class MediaSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament-media::pages.media-settings';

    protected static ?int $navigationSort = 100;

    public ?array $data = [];

    public array $orphanedFiles = [];

    public array $selectedOrphans = [];

    public bool $scanComplete = false;

    public bool $scanning = false;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return config('media.navigation.settings.icon', 'heroicon-o-cog-6-tooth');
    }

    public static function getNavigationLabel(): string
    {
        $label = config('media.navigation.settings.label');

        return $label ?: trans('filament-media::media.settings.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('media.navigation.shared_group')
            ?? config('media.navigation.settings.group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('media.navigation.settings.sort', 2);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('media.navigation.settings.visible', true);
    }

    public function getTitle(): string
    {
        return trans('filament-media::media.settings.title');
    }

    /**
     * Check if user has permission to access settings.
     */
    public static function canAccess(): bool
    {
        // Check for custom permission if configured
        if (FilamentMedia::hasPermission('settings.access')) {
            return true;
        }

        // Check config for who can access settings
        $accessLevel = FilamentMedia::getConfig('settings.access', 'all');

        if ($accessLevel === 'super_admin') {
            // Check if user is super admin (implementation depends on your auth setup)
            return auth()->user()?->hasRole('super_admin') ?? false;
        }

        // Default: allow all authenticated admin users
        return true;
    }

    public function mount(): void
    {
        $this->form->fill($this->loadSettings());
    }

    protected function loadSettings(): array
    {
        return [
            // Storage settings
            'storage_driver' => setting('media_driver', config('media.driver', 'public')),
            'custom_upload_path' => setting('media_customize_upload_path', false),
            'upload_path' => setting('media_upload_path', ''),
            'use_symlink' => setting('media_use_storage_symlink', config('media.use_storage_symlink', false)),
            's3_bucket' => setting('media_s3_bucket', config('filesystems.disks.s3.bucket', '')),
            's3_region' => setting('media_s3_region', config('filesystems.disks.s3.region', '')),
            's3_cdn_url' => setting('media_s3_cdn_url', ''),

            // File type settings
            'allowed_extensions' => $this->parseExtensions(setting('media_allowed_mime_types', config('media.allowed_mime_types', ''))),
            'max_file_size' => (int) (setting('media_max_file_size', config('media.max_file_size', 10485760)) / 1024 / 1024),

            // Thumbnail settings
            'generate_thumbnails' => setting('media_generate_thumbnails_enabled', config('media.generate_thumbnails_enabled', true)),
            'thumbnail_sizes' => $this->parseThumbnailSizes(setting('media_sizes', config('media.sizes', ['thumb' => '150x150']))),
            'image_library' => setting('media_image_processing_library', 'gd'),

            // Watermark settings
            'watermark_enabled' => setting('media_watermark_enabled', config('media.watermark.enabled', false)),
            'watermark_position' => setting('media_watermark_position', config('media.watermark.position', 'bottom-right')),
            'watermark_opacity' => setting('media_watermark_opacity', config('media.watermark.opacity', 70)),
            'watermark_size' => setting('media_watermark_size', config('media.watermark.size', 10)),

            // Chunk upload settings
            'chunk_enabled' => setting('media_chunk_enabled', config('media.chunk.enabled', false)),
            'chunk_size' => (int) (setting('media_chunk_size', config('media.chunk.chunk_size', 1048576)) / 1024 / 1024),
        ];
    }

    protected function parseExtensions(string $extensions): array
    {
        if (empty($extensions)) {
            return [];
        }

        return array_map('trim', explode(',', $extensions));
    }

    protected function parseThumbnailSizes(array $sizes): array
    {
        $result = [];
        foreach ($sizes as $name => $dimensions) {
            if (is_string($dimensions) && str_contains($dimensions, 'x')) {
                [$width, $height] = explode('x', $dimensions);
                $result[] = [
                    'name' => $name,
                    'width' => (int) $width,
                    'height' => (int) $height,
                ];
            }
        }
        return $result;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->Components([
                // Storage Configuration Section
                Section::make(trans('filament-media::media.settings.storage'))
                    ->description(trans('filament-media::media.settings.storage_description'))
                    ->schema([
                        Select::make('storage_driver')
                            ->label(trans('filament-media::media.settings.storage_driver'))
                            ->options([
                                'public' => trans('filament-media::media.settings.driver_public'),
                                'local' => trans('filament-media::media.settings.driver_local'),
                                's3' => 'Amazon S3',
                                'r2' => 'Cloudflare R2',
                                'do_spaces' => 'DigitalOcean Spaces',
                                'wasabi' => 'Wasabi',
                                'backblaze' => 'Backblaze B2',
                            ])
                            ->default('public')
                            ->live()
                            ->helperText(trans('filament-media::media.settings.storage_driver_help')),

                        // Cloud storage notice
                        TextEntry::make('cloud_credentials_notice')
                            //TODO: remove this line?
                            //->content(trans('filament-media::media.settings.cloud_credentials_notice'))
                            ->visible(fn($get) => in_array($get('storage_driver'), ['s3', 'r2', 'do_spaces', 'wasabi', 'backblaze']))
                            ->extraAttributes(['class' => 'text-warning-600 dark:text-warning-400']),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('s3_bucket')
                                    ->label(trans('filament-media::media.settings.bucket_name'))
                                    ->visible(fn($get) => in_array($get('storage_driver'), ['s3', 'r2', 'do_spaces', 'wasabi', 'backblaze'])),

                                TextInput::make('s3_region')
                                    ->label(trans('filament-media::media.settings.region'))
                                    ->visible(fn($get) => in_array($get('storage_driver'), ['s3', 'do_spaces'])),
                            ]),

                        TextInput::make('s3_cdn_url')
                            ->label(trans('filament-media::media.settings.cdn_url'))
                            ->url()
                            ->placeholder('https://cdn.example.com')
                            ->visible(fn($get) => in_array($get('storage_driver'), ['s3', 'r2', 'do_spaces', 'wasabi', 'backblaze']))
                            ->helperText(trans('filament-media::media.settings.cdn_url_help')),

                        Toggle::make('custom_upload_path')
                            ->label(trans('filament-media::media.settings.custom_upload_path'))
                            ->live(),

                        TextInput::make('upload_path')
                            ->label(trans('filament-media::media.settings.upload_path'))
                            ->placeholder('media')
                            ->visible(fn($get) => $get('custom_upload_path'))
                            ->helperText(trans('filament-media::media.settings.upload_path_help')),

                        Toggle::make('use_symlink')
                            ->label(trans('filament-media::media.settings.use_symlink'))
                            ->helperText(trans('filament-media::media.settings.use_symlink_help')),
                    ])
                    ->collapsible(),

                // Allowed File Types Section
                Section::make(trans('filament-media::media.settings.file_types'))
                    ->description(trans('filament-media::media.settings.file_types_description'))
                    ->schema([
                        TagsInput::make('allowed_extensions')
                            ->label(trans('filament-media::media.settings.allowed_extensions'))
                            ->placeholder(trans('filament-media::media.settings.add_extension'))
                            ->helperText(trans('filament-media::media.settings.allowed_extensions_help'))
                            ->separator(','),

                        TextInput::make('max_file_size')
                            ->label(trans('filament-media::media.settings.max_file_size'))
                            ->numeric()
                            ->suffix('MB')
                            ->default(10)
                            ->minValue(1)
                            ->maxValue(1024)
                            ->helperText(trans('filament-media::media.settings.max_file_size_help')),
                    ])
                    ->collapsible(),

                // Thumbnails Section
                Section::make(trans('filament-media::media.settings.thumbnails'))
                    ->description(trans('filament-media::media.settings.thumbnails_description'))
                    ->schema([
                        Toggle::make('generate_thumbnails')
                            ->label(trans('filament-media::media.settings.generate_thumbnails'))
                            ->live(),

                        Repeater::make('thumbnail_sizes')
                            ->label(trans('filament-media::media.settings.thumbnail_sizes'))
                            ->visible(fn($get) => $get('generate_thumbnails'))
                            ->schema([
                                TextInput::make('name')
                                    ->label(trans('filament-media::media.settings.size_name'))
                                    ->required()
                                    ->maxLength(50),
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('width')
                                            ->label(trans('filament-media::media.settings.width'))
                                            ->numeric()
                                            ->required()
                                            ->suffix('px'),
                                        TextInput::make('height')
                                            ->label(trans('filament-media::media.settings.height'))
                                            ->numeric()
                                            ->required()
                                            ->suffix('px'),
                                    ]),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->reorderable()
                            ->addActionLabel(trans('filament-media::media.settings.add_size')),

                        Select::make('image_library')
                            ->label(trans('filament-media::media.settings.image_library'))
                            ->options([
                                'gd' => 'GD Library',
                                'imagick' => 'ImageMagick',
                            ])
                            ->default('gd')
                            ->helperText(trans('filament-media::media.settings.image_library_help')),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // Watermark Section
                Section::make(trans('filament-media::media.settings.watermark'))
                    ->description(trans('filament-media::media.settings.watermark_description'))
                    ->schema([
                        Toggle::make('watermark_enabled')
                            ->label(trans('filament-media::media.settings.enable_watermark'))
                            ->live(),

                        Grid::make(2)
                            ->visible(fn($get) => $get('watermark_enabled'))
                            ->schema([
                                Select::make('watermark_position')
                                    ->label(trans('filament-media::media.settings.watermark_position'))
                                    ->options([
                                        'top-left' => trans('filament-media::media.settings.position_top_left'),
                                        'top-right' => trans('filament-media::media.settings.position_top_right'),
                                        'bottom-left' => trans('filament-media::media.settings.position_bottom_left'),
                                        'bottom-right' => trans('filament-media::media.settings.position_bottom_right'),
                                        'center' => trans('filament-media::media.settings.position_center'),
                                    ])
                                    ->default('bottom-right'),

                                TextInput::make('watermark_opacity')
                                    ->label(trans('filament-media::media.settings.watermark_opacity'))
                                    ->numeric()
                                    ->suffix('%')
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->default(70),

                                TextInput::make('watermark_size')
                                    ->label(trans('filament-media::media.settings.watermark_size'))
                                    ->numeric()
                                    ->suffix('%')
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->default(10)
                                    ->helperText(trans('filament-media::media.settings.watermark_size_help')),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // Chunk Upload Section
                Section::make(trans('filament-media::media.settings.chunk_upload'))
                    ->description(trans('filament-media::media.settings.chunk_upload_description'))
                    ->schema([
                        Toggle::make('chunk_enabled')
                            ->label(trans('filament-media::media.settings.enable_chunk_upload'))
                            ->live(),

                        TextInput::make('chunk_size')
                            ->label(trans('filament-media::media.settings.chunk_size'))
                            ->visible(fn($get) => $get('chunk_enabled'))
                            ->numeric()
                            ->suffix('MB')
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText(trans('filament-media::media.settings.chunk_size_help')),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Save settings to database
        $this->saveSettings($data);

        // Clear settings cache
        clear_media_settings_cache();

        Notification::make()
            ->title(trans('filament-media::media.settings.saved'))
            ->success()
            ->send();
    }

    protected function saveSettings(array $data): void
    {
        // Storage settings
        MediaSetting::setSystemSetting('media_driver', $data['storage_driver']);
        MediaSetting::setSystemSetting('media_customize_upload_path', $data['custom_upload_path'] ?? false);
        MediaSetting::setSystemSetting('media_upload_path', $data['upload_path'] ?? '');
        MediaSetting::setSystemSetting('media_use_storage_symlink', $data['use_symlink'] ?? false);

        // Cloud storage settings (non-sensitive only)
        if (in_array($data['storage_driver'], ['s3', 'r2', 'do_spaces', 'wasabi', 'backblaze'])) {
            MediaSetting::setSystemSetting('media_s3_bucket', $data['s3_bucket'] ?? '');
            MediaSetting::setSystemSetting('media_s3_region', $data['s3_region'] ?? '');
            MediaSetting::setSystemSetting('media_s3_cdn_url', $data['s3_cdn_url'] ?? '');
        }

        // File type settings
        $extensions = is_array($data['allowed_extensions'])
            ? implode(',', $data['allowed_extensions'])
            : $data['allowed_extensions'];
        MediaSetting::setSystemSetting('media_allowed_mime_types', $extensions);
        MediaSetting::setSystemSetting('media_max_file_size', ($data['max_file_size'] ?? 10) * 1024 * 1024);

        // Thumbnail settings
        MediaSetting::setSystemSetting('media_generate_thumbnails_enabled', $data['generate_thumbnails'] ?? true);
        MediaSetting::setSystemSetting('media_image_processing_library', $data['image_library'] ?? 'gd');

        // Convert thumbnail sizes array to the expected format
        $sizes = [];
        foreach ($data['thumbnail_sizes'] ?? [] as $size) {
            if (!empty($size['name']) && !empty($size['width']) && !empty($size['height'])) {
                $sizes[$size['name']] = $size['width'] . 'x' . $size['height'];
            }
        }
        MediaSetting::setSystemSetting('media_sizes', $sizes);

        // Watermark settings
        MediaSetting::setSystemSetting('media_watermark_enabled', $data['watermark_enabled'] ?? false);
        MediaSetting::setSystemSetting('media_watermark_position', $data['watermark_position'] ?? 'bottom-right');
        MediaSetting::setSystemSetting('media_watermark_opacity', $data['watermark_opacity'] ?? 70);
        MediaSetting::setSystemSetting('media_watermark_size', $data['watermark_size'] ?? 10);

        // Chunk upload settings
        MediaSetting::setSystemSetting('media_chunk_enabled', $data['chunk_enabled'] ?? false);
        MediaSetting::setSystemSetting('media_chunk_size', ($data['chunk_size'] ?? 1) * 1024 * 1024);
    }

    // ──────────────────────────────────────────────────
    // Orphan Scan
    // ──────────────────────────────────────────────────

    public function scanStorage(): void
    {
        $this->scanning = true;
        $this->orphanedFiles = [];
        $this->selectedOrphans = [];
        $this->scanComplete = false;

        $service = app(OrphanScanService::class);
        $results = $service->scan();

        $this->orphanedFiles = $results->map(fn (array $file) => [
            'path' => $file['path'],
            'name' => $file['name'],
            'size' => BaseHelper::humanFilesize($file['size']),
            'size_raw' => $file['size'],
            'mime_type' => $file['mime_type'],
        ])->toArray();

        $this->scanning = false;
        $this->scanComplete = true;

        if (empty($this->orphanedFiles)) {
            Notification::make()
                ->title(trans('filament-media::media.settings.scan_no_orphans'))
                ->success()
                ->send();
        }
    }

    public function importOrphans(): void
    {
        if (empty($this->selectedOrphans)) {
            Notification::make()
                ->title(trans('filament-media::media.settings.scan_select_files'))
                ->warning()
                ->send();

            return;
        }

        $service = app(OrphanScanService::class);
        $imported = $service->import($this->selectedOrphans, 0, auth()->id());

        Notification::make()
            ->title(trans('filament-media::media.settings.scan_imported', ['count' => $imported]))
            ->success()
            ->send();

        // Re-scan to refresh the list
        $this->scanStorage();
    }

    public function deleteOrphans(): void
    {
        if (empty($this->selectedOrphans)) {
            Notification::make()
                ->title(trans('filament-media::media.settings.scan_select_files'))
                ->warning()
                ->send();

            return;
        }

        $service = app(OrphanScanService::class);
        $deleted = $service->delete($this->selectedOrphans);

        Notification::make()
            ->title(trans('filament-media::media.settings.scan_deleted', ['count' => $deleted]))
            ->success()
            ->send();

        // Re-scan to refresh the list
        $this->scanStorage();
    }

    public function importAllOrphans(): void
    {
        $paths = array_column($this->orphanedFiles, 'path');

        $service = app(OrphanScanService::class);
        $imported = $service->import($paths, 0, auth()->id());

        Notification::make()
            ->title(trans('filament-media::media.settings.scan_imported', ['count' => $imported]))
            ->success()
            ->send();

        $this->scanStorage();
    }

    public function deleteAllOrphans(): void
    {
        $paths = array_column($this->orphanedFiles, 'path');

        $service = app(OrphanScanService::class);
        $deleted = $service->delete($paths);

        Notification::make()
            ->title(trans('filament-media::media.settings.scan_deleted', ['count' => $deleted]))
            ->success()
            ->send();

        $this->scanStorage();
    }

    public function toggleOrphanSelection(string $path): void
    {
        if (in_array($path, $this->selectedOrphans)) {
            $this->selectedOrphans = array_values(array_diff($this->selectedOrphans, [$path]));
        } else {
            $this->selectedOrphans[] = $path;
        }
    }

    public function toggleAllOrphans(): void
    {
        $allPaths = array_column($this->orphanedFiles, 'path');

        if (count($this->selectedOrphans) === count($allPaths)) {
            $this->selectedOrphans = [];
        } else {
            $this->selectedOrphans = $allPaths;
        }
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label(trans('filament-media::media.settings.save'))
                ->submit('save'),
        ];
    }
}
