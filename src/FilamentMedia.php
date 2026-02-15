<?php

namespace Codenzia\FilamentMedia;

use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Services\ImageService;
use Codenzia\FilamentMedia\Services\MediaUrlService;
use Codenzia\FilamentMedia\Services\StorageDriverService;
use Codenzia\FilamentMedia\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

/**
 * Central service class for the Filament Media package.
 *
 * Provides configuration access, permission management, folder creation,
 * service delegation for URL/file/image operations, and JSON response helpers.
 */
class FilamentMedia
{
    protected array $permissions = [];

    protected array $defaultPermissions = [
        'folders.create',
        'folders.edit',
        'folders.trash',
        'folders.destroy',
        'folders.favorite',
        'files.create',
        'files.read',
        'files.edit',
        'files.trash',
        'files.destroy',
        'files.favorite',
        'settings.access',
    ];

    // ──────────────────────────────────────────────────
    // Config
    // ──────────────────────────────────────────────────

    public static function getConfig(?string $key = null, mixed $default = null): mixed
    {
        $configs = config('filament-media.media') ?? config('media');

        if (! $key) {
            return $configs;
        }

        return Arr::get($configs, $key, $default);
    }

    public static function getSorts(): array
    {
        return [
            'name-asc' => ['label' => trans('filament-media::media.file_name_asc'), 'icon' => 'heroicon-m-chevron-up-down'],
            'name-desc' => ['label' => trans('filament-media::media.file_name_desc'), 'icon' => 'heroicon-m-chevron-up-down'],
            'created_at-asc' => ['label' => trans('filament-media::media.uploaded_date_asc'), 'icon' => 'heroicon-m-chevron-up-down'],
            'created_at-desc' => ['label' => trans('filament-media::media.uploaded_date_desc'), 'icon' => 'heroicon-m-chevron-up-down'],
            'size-asc' => ['label' => trans('filament-media::media.size_asc'), 'icon' => 'heroicon-m-chevron-up-down'],
            'size-desc' => ['label' => trans('filament-media::media.size_desc'), 'icon' => 'heroicon-m-chevron-up-down'],
        ];
    }

    public static function transformOrderBy(?string $orderBy): array
    {
        if (! $orderBy) {
            return ['name', 'asc'];
        }

        $parts = explode('-', $orderBy, 2);

        return [$parts[0] ?? 'name', $parts[1] ?? 'asc'];
    }

    // ──────────────────────────────────────────────────
    // Permissions
    // ──────────────────────────────────────────────────

    public function getPermissions(): array
    {
        $configPermissions = $this->permissions ?: static::getConfig('permissions', []);

        return array_unique(array_merge($this->defaultPermissions, $configPermissions));
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getPermissions());
    }

    public function hasAnyPermission(array $permissions): bool
    {
        $available = $this->getPermissions();

        foreach ($permissions as $permission) {
            if (in_array($permission, $available)) {
                return true;
            }
        }

        return false;
    }

    public function setPermissions(array $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function addPermission(string $permission): void
    {
        $this->permissions[] = $permission;
    }

    public function removePermission(string $permission): void
    {
        Arr::forget($this->permissions, $permission);
    }

    // ──────────────────────────────────────────────────
    // Folder creation
    // ──────────────────────────────────────────────────

    public function createFolder(string $slug, int|string|null $parentId = 0): int|string
    {
        $folder = MediaFolder::query()
            ->where(['slug' => $slug, 'parent_id' => $parentId])
            ->first();

        if (! $folder) {
            $folder = MediaFolder::query()->create([
                'user_id' => Auth::guard()->check() ? Auth::guard()->id() : 0,
                'name' => MediaFolder::createName($slug, $parentId),
                'slug' => MediaFolder::createSlug($slug, $parentId),
                'parent_id' => $parentId,
            ]);
        }

        return $folder->id;
    }

    public function isChunkUploadEnabled(): bool
    {
        return (bool) \setting('media_chunk_enabled', (int) static::getConfig('chunk.enabled') == 1);
    }

    public function canOnlyViewOwnMedia(): bool
    {
        return false;
    }

    public function turnOffAutomaticUrlTranslationIntoLatin(): bool
    {
        return (int) \setting('media_turn_off_automatic_url_translation_into_latin', 0) == 1;
    }

    public function getFolderColors(): array
    {
        return static::getConfig('folder_colors', []);
    }

    // ──────────────────────────────────────────────────
    // Service delegation (transitional - for code that still calls the Facade)
    // ──────────────────────────────────────────────────

    public function url(?string $path): string
    {
        return app(MediaUrlService::class)->url($path);
    }

    public function getRealPath(?string $url): ?string
    {
        return app(MediaUrlService::class)->getRealPath($url);
    }

    public function getMimeType(?string $url): ?string
    {
        return app(MediaUrlService::class)->getMimeType($url);
    }

    public function isImage(string $mimeType): bool
    {
        return app(MediaUrlService::class)->isImage($mimeType);
    }

    public function isUsingCloud(): bool
    {
        return app(StorageDriverService::class)->isUsingCloud();
    }

    public function getMediaDriver(): string
    {
        return app(StorageDriverService::class)->getMediaDriver();
    }

    public function canGenerateThumbnails(?string $mimeType): bool
    {
        return app(ImageService::class)->canGenerateThumbnails($mimeType);
    }

    public function deleteFile($file): bool
    {
        return app(\Codenzia\FilamentMedia\Services\FileOperationService::class)->deleteFile($file);
    }

    public function getDefaultImage(bool $relative = false, ?string $size = null): string
    {
        return app(MediaUrlService::class)->getDefaultImage($relative, $size);
    }

    public function getAllowedMimeTypesString(): string
    {
        return app(UploadService::class)->getAllowedMimeTypesString();
    }

    public function getMaxSize(): int
    {
        return app(UploadService::class)->getMaxSize();
    }

    public function getUrls(): array
    {
        return [];
    }

    // ──────────────────────────────────────────────────
    // Response helpers
    // ──────────────────────────────────────────────────

    public function responseSuccess(array $data, ?string $message = null): JsonResponse
    {
        return response()->json([
            'error' => false,
            'data' => $data,
            'message' => $message,
        ]);
    }

    public function responseError(
        string $message,
        array $data = [],
        ?int $code = null,
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'error' => true,
            'message' => $message,
            'data' => $data,
            'code' => $code,
        ], $status);
    }
}
