<?php

namespace Codenzia\FilamentMedia\Models;

use Codenzia\FilamentMedia\Database\Factories\MediaFileFactory;
use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Codenzia\FilamentMedia\Helpers\BaseHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * Represents an uploaded media file with metadata, tags, versioning, and folder organization.
 */
class MediaFile extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'media_files';

    protected $fillable = [
        'name',
        'mime_type',
        'type',
        'size',
        'url',
        'options',
        'folder_id',
        'user_id',
        'alt',
        'description',
        'visibility',
        'fileable_type',
        'fileable_id',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'options' => 'json',
    ];

    protected $appends = [
        'indirect_url',
    ];

    protected static function newFactory(): MediaFileFactory
    {
        return MediaFileFactory::new();
    }

    protected static function booted(): void
    {
        static::addGlobalScope('ownMedia', function (Builder $query): void {
            $user = auth()->user();

            if (! $user) {
                return;
            }

            $scopeCallback = app(\Codenzia\FilamentMedia\FilamentMedia::class)->getMediaQueryScope();

            if ($scopeCallback) {
                call_user_func($scopeCallback, $query, $user);

                return;
            }

            if (FilamentMedia::canOnlyViewOwnMedia()) {
                $query->where('media_files.user_id', $user->getAuthIdentifier());
            }
        });
    }

    // ──────────────────────────────────────────────────
    // Relations
    // ──────────────────────────────────────────────────

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id')->withDefault();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'updated_by_user_id');
    }

    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(MediaTag::class, 'media_file_tag');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(MediaTag::class, 'media_file_tag')
            ->where('media_tags.type', 'collection');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(MediaFileVersion::class, 'media_file_id')
            ->orderByDesc('version_number');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(MediaFileVersion::class, 'media_file_id')
            ->orderByDesc('version_number');
    }

    public function metadata(): BelongsToMany
    {
        return $this->belongsToMany(MediaMetadataField::class, 'media_file_metadata')
            ->withPivot('value')
            ->withTimestamps();
    }

    // ──────────────────────────────────────────────────
    // Query Scopes
    // ──────────────────────────────────────────────────

    public function scopeInFolder(Builder $query, int|string|null $folderId): Builder
    {
        return $query->where('media_files.folder_id', $folderId ?? 0);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        $mimeTypes = config('media.mime_types.' . $type, []);

        if (empty($mimeTypes)) {
            return $query;
        }

        return $query->whereIn('media_files.mime_type', $mimeTypes);
    }

    public function scopeOfMimeType(Builder $query, array $mimeTypes): Builder
    {
        return $query->whereIn('media_files.mime_type', $mimeTypes);
    }

    /**
     * Filter by media type. Supports 'everything' to skip filtering.
     */
    public function scopeFilterByType(Builder $query, string $filter): Builder
    {
        if (empty($filter) || $filter === 'everything') {
            return $query;
        }

        $allMimeTypes = config('media.mime_types', []);
        $filterMimes = $allMimeTypes[$filter] ?? null;

        if ($filterMimes) {
            return $query->whereIn('media_files.mime_type', $filterMimes);
        }

        // Filter doesn't match any known type - show files not in any known type
        $allMimes = collect($allMimeTypes)->flatten()->unique()->toArray();

        return $query->whereNotIn('media_files.mime_type', $allMimes);
    }

    public function scopeSorted(Builder $query, string $sortBy): Builder
    {
        $parts = explode('-', $sortBy, 2);
        $column = $parts[0] ?? 'name';
        $direction = $parts[1] ?? 'asc';

        if (! in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = 'asc';
        }

        return $query->orderBy($column, $direction);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        $term = '%' . $search . '%';

        return $query->where(function (Builder $q) use ($term): void {
            $q->where('media_files.name', 'LIKE', $term)
                ->orWhere('media_files.alt', 'LIKE', $term)
                ->orWhere('media_files.description', 'LIKE', $term);
        });
    }

    public function scopeTagged(Builder $query, array $tagIds): Builder
    {
        return $query->whereHas('tags', function (Builder $q) use ($tagIds): void {
            $q->whereIn('media_tags.id', $tagIds);
        });
    }

    public function scopeInCollection(Builder $query, int $collectionId): Builder
    {
        return $query->whereHas('collections', function (Builder $q) use ($collectionId): void {
            $q->where('media_tags.id', $collectionId);
        });
    }

    public function scopeWithMetadataValue(Builder $query, string $fieldSlug, ?string $value = null): Builder
    {
        return $query->whereHas('metadata', function (Builder $q) use ($fieldSlug, $value): void {
            $q->where('media_metadata_fields.slug', $fieldSlug);

            if ($value !== null) {
                $q->where('media_file_metadata.value', $value);
            }
        });
    }

    // ──────────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────────

    protected function type(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                foreach (config('media.mime_types', []) as $key => $mimes) {
                    if (in_array($attributes['mime_type'] ?? '', $mimes)) {
                        return $key;
                    }
                }

                return 'document';
            }
        );
    }

    protected function humanSize(): Attribute
    {
        return Attribute::get(fn () => BaseHelper::humanFilesize($this->size));
    }

    protected function icon(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                $mimeType = $attributes['mime_type'] ?? '';

                $iconMap = [
                    'image/' => 'heroicon-m-photo',
                    'video/' => 'heroicon-m-video-camera',
                    'audio/' => 'heroicon-m-musical-note',
                    'application/pdf' => 'heroicon-m-document',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml' => 'heroicon-m-document-text',
                    'application/msword' => 'heroicon-m-document-text',
                    'application/vnd.ms-excel' => 'heroicon-m-table-cells',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml' => 'heroicon-m-table-cells',
                    'application/excel' => 'heroicon-m-table-cells',
                    'application/vnd.ms-powerpoint' => 'heroicon-m-presentation-chart-bar',
                    'application/vnd.openxmlformats-officedocument.presentationml' => 'heroicon-m-presentation-chart-bar',
                    'application/zip' => 'heroicon-m-archive-box',
                    'application/x-zip' => 'heroicon-m-archive-box',
                    'text/' => 'heroicon-m-document-text',
                ];

                foreach ($iconMap as $prefix => $icon) {
                    if (str_starts_with($mimeType, $prefix)) {
                        return BaseHelper::renderIcon($icon);
                    }
                }

                return BaseHelper::renderIcon('heroicon-m-document');
            }
        );
    }

    protected function previewUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            $isPrivate = $this->visibility === 'private';
            $privateUrl = $isPrivate ? $this->privateRouteUrl() : null;

            if (str_starts_with($this->mime_type, 'image/')) {
                return $isPrivate ? $privateUrl : FilamentMedia::url($this->url);
            }

            if (str_starts_with($this->mime_type, 'video/') || str_starts_with($this->mime_type, 'audio/')) {
                return $isPrivate ? $privateUrl : FilamentMedia::url($this->url);
            }

            if ($this->mime_type === 'application/pdf') {
                return $isPrivate ? $privateUrl : FilamentMedia::url($this->url);
            }

            // Office document preview via external provider (public only — external providers cannot access private files)
            $config = config('media.preview.document', []);
            if (
                ! $isPrivate
                && Arr::get($config, 'enabled')
                && Request::ip() !== '127.0.0.1'
                && in_array($this->mime_type, Arr::get($config, 'mime_types', []))
            ) {
                $provider = Arr::get($config, 'providers.' . Arr::get($config, 'default'));
                if ($provider) {
                    return Str::replace('{url}', urlencode(FilamentMedia::url($this->url)), $provider);
                }
            }

            return null;
        });
    }

    protected function privateRouteUrl(): string
    {
        $id = $this->getKey();
        $hash = sha1($id);

        return route('media.private.url', compact('hash', 'id'));
    }

    protected function previewType(): Attribute
    {
        return Attribute::get(fn () => Arr::get(config('media.preview', []), "$this->type.type"));
    }

    protected function indirectUrl(): Attribute
    {
        return Attribute::get(function () {
            $id = $this->getKey() ?: dechex((int) $this->getKey());
            $hash = sha1($id);

            if ($this->visibility === 'private') {
                return route('media.private.url', compact('hash', 'id'));
            }

            return route('media.indirect.url', compact('hash', 'id'));
        })->shouldCache();
    }

    // ──────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────

    public function getLinkedModelInfo(): ?array
    {
        if (! $this->fileable_type || ! $this->fileable_id) {
            return null;
        }

        $modelClass = $this->fileable_type;
        $modelId = $this->fileable_id;
        $typeName = class_basename($modelClass);

        $model = $this->fileable;
        $displayName = $model?->name ?? $model?->title ?? "#{$modelId}";

        $url = null;

        try {
            $resource = \Filament\Facades\Filament::getModelResource($modelClass);

            if ($resource) {
                $url = $resource::getUrl('edit', ['record' => $modelId]);
            }
        } catch (\Throwable) {
        }

        $attributes = [];

        if ($model) {
            $hidden = array_flip($model->getHidden());

            foreach ($model->attributesToArray() as $key => $value) {
                if (isset($hidden[$key]) || is_array($value)) {
                    continue;
                }

                $attributes[$key] = $value;
            }
        }

        return [
            'type' => $typeName,
            'name' => $displayName,
            'id' => $modelId,
            'url' => $url,
            'label' => "{$typeName}: {$displayName}",
            'attributes' => $attributes,
        ];
    }

    public function canGenerateThumbnails(): bool
    {
        return FilamentMedia::canGenerateThumbnails($this->mime_type);
    }

    /**
     * Create a unique name for a file in the given folder.
     */
    public static function createName(string $name, int|string|null $folder): string
    {
        $baseName = $name;
        $likePattern = str_replace(['%', '_'], ['\%', '\_'], $baseName);

        $existingNames = self::query()
            ->where('folder_id', $folder)
            ->where(function ($query) use ($likePattern, $baseName) {
                $query->where('name', $baseName)
                    ->orWhere('name', 'LIKE', $likePattern . '-%');
            })
            ->withTrashed()
            ->pluck('name')
            ->toArray();

        if (empty($existingNames) || ! in_array($baseName, $existingNames)) {
            return $name;
        }

        $maxSuffix = 0;
        foreach ($existingNames as $existingName) {
            if (preg_match('/^' . preg_quote($baseName, '/') . '-(\d+)$/', $existingName, $matches)) {
                $maxSuffix = max($maxSuffix, (int) $matches[1]);
            }
        }

        return $baseName . '-' . ($maxSuffix + 1);
    }

    public static function createSlug(string $name, string $extension, ?string $folderPath): string
    {
        if (\setting('media_convert_file_name_to_uuid')) {
            return Str::uuid() . '.' . $extension;
        }

        $slug = \setting('media_use_original_name_for_file_path')
            ? $name
            : Str::slug($name, '-', ! FilamentMedia::turnOffAutomaticUrlTranslationIntoLatin() ? 'en' : false);

        $index = 1;
        $baseSlug = $slug;

        while (File::exists(FilamentMedia::getRealPath(
            rtrim($folderPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $slug . '.' . $extension
        ))) {
            $slug = $baseSlug . '-' . $index++;
        }

        if (empty($slug)) {
            $slug = 'file-' . time();
        }

        return Str::limit($slug, end: '') . '.' . $extension;
    }
}
