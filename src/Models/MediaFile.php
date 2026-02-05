<?php

namespace Codenzia\FilamentMedia\Models;

use Codenzia\FilamentMedia\Database\Factories\MediaFileFactory;
use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Codenzia\FilamentMedia\Helpers\BaseHelper;
use Codenzia\FilamentMedia\Services\SafeContentService;

class MediaFile extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static function newFactory(): MediaFileFactory
    {
        return MediaFileFactory::new();
    }

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
        'visibility',
    ];

    protected $casts = [
        'options' => 'json',
        //'name' => SafeContent::class,
    ];

    protected $appends = [
        'indirect_url',
    ];

    protected static function booted(): void
    {
        static::forceDeleted(fn (MediaFile $file) => FilamentMedia::deleteFile($file));

        static::addGlobalScope('ownMedia', function (Builder $query): void {
            if (FilamentMedia::canOnlyViewOwnMedia()) {
                $query->where('media_files.user_id', auth()->id());
            }
        });
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id')->withDefault();
    }

    /**
     * Get the user who owns this file.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'user_id');
    }

    /**
     * Get the user who created this file.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'created_by_user_id');
    }

    /**
     * Get the user who last updated this file.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'updated_by_user_id');
    }

    /**
     * Get the parent model that this file is attached to.
     */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function type(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                $type = 'document';

                foreach (FilamentMedia::getConfig('mime_types', []) as $key => $value) {
                    if (in_array($attributes['mime_type'], $value)) {
                        $type = $key;

                        break;
                    }
                }

                return $type;
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
                $types = [
                    'jpeg' => [
                        'image/jpeg',
                        'image/jpg',
                    ],
                    'png' => [
                        'image/png',
                    ],
                    'gif' => [
                        'image/gif',
                    ],
                    'video' => [
                        'video/mp4',
                        'video/m4v',
                        'video/mov',
                        'video/quicktime',
                    ],
                    'document' => [
                        'text/plain',
                        'text/csv',
                    ],
                    'zip' => [
                        'application/zip',
                        'application/x-zip-compressed',
                        'application/x-compressed',
                        'multipart/x-zip',
                    ],
                    'audio' => [
                        'audio/mpeg',
                        'audio/mp3',
                        'audio/wav',
                    ],
                    'docx' => [
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ],
                    'doc' => [
                        'application/msword',
                    ],
                    'excel' => [
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'application/excel',
                        'application/x-excel',
                        'application/x-msexcel',
                    ],
                    'pdf' => [
                        'application/pdf',
                    ],
                    'powerpoint' => [
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    ],
                ];

                $type = $this->type;

                foreach ($types as $key => $value) {
                    if (in_array($attributes['mime_type'], $value)) {
                        $type = $key;

                        break;
                    }
                }

                $icon = match ($type) {
                    'image' => 'heroicon-m-photo',
                    'video' => 'heroicon-m-video-camera',
                    'pdf' => 'heroicon-m-document',
                    'excel' => 'heroicon-m-table-cells',
                    'zip' => 'heroicon-m-archive-box',
                    'docx' => 'heroicon-m-document-text',
                    'doc' => 'heroicon-m-document-text',
                    'powerpoint' => 'heroicon-m-presentation-chart-bar',
                    'jpeg' => 'heroicon-m-photo',
                    'png' => 'heroicon-m-photo',
                    'gif' => 'heroicon-m-photo',
                    default => 'heroicon-m-document',
                };

                return BaseHelper::renderIcon($icon);
            }
        );
    }

    protected function previewUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            $preview = null;

            switch ($this->type) {
                case 'image':
                case 'jpeg':
                case 'png':
                case 'gif':
                    if ($this->visibility === 'public') {
                        $preview = FilamentMedia::url($this->url);
                    }

                    break;
                case 'text':
                case 'video':
                    $preview = FilamentMedia::url($this->url);

                    break;
                case 'document':
                case 'pdf':
                case 'doc':
                case 'docx':
                case 'excel':
                case 'powerpoint':
                    if ($this->mime_type === 'application/pdf' && $this->visibility === 'public') {
                        $preview = FilamentMedia::url($this->url);

                        break;
                    }

                    $config = config('core.media.media.preview.document', []);
                    if (
                        $this->visibility === 'public' &&
                        Arr::get($config, 'enabled') &&
                        Request::ip() !== '127.0.0.1' &&
                        in_array($this->mime_type, Arr::get($config, 'mime_types', [])) &&
                        $url = Arr::get($config, 'providers.' . Arr::get($config, 'default'))
                    ) {
                        $preview = Str::replace('{url}', urlencode(FilamentMedia::url($this->url)), $url);
                    }

                    break;
            }

            return $preview;
        });
    }

    protected function previewType(): Attribute
    {
        return Attribute::get(fn () => Arr::get(config('core.media.media.preview', []), "$this->type.type"));
    }

    protected function indirectUrl(): Attribute
    {
        return Attribute::get(function () {
            $id = $this->getKey()
                ? $this->getKey()
                : dechex((int) $this->getKey());
            $hash = sha1($id);

            return route('media.indirect.url', compact('hash', 'id'));
        })->shouldCache();
    }

    public function canGenerateThumbnails(): bool
    {
        return (! $this->visibility || $this->visibility === 'public') && FilamentMedia::canGenerateThumbnails($this->mime_type);
    }

    /**
     * Create a unique name for a file in the given folder.
     * Optimized to use a single query to find all existing names with the same base.
     */
    public static function createName(string $name, int|string|null $folder): string
    {
        // Escape special regex characters in the name for LIKE query
        $baseName = $name;
        $likePattern = str_replace(['%', '_'], ['\%', '\_'], $baseName);

        // Get all existing names that match the pattern (including suffixes like -1, -2, etc.)
        $existingNames = self::query()
            ->where('folder_id', $folder)
            ->where(function ($query) use ($likePattern, $baseName) {
                $query->where('name', $baseName)
                    ->orWhere('name', 'LIKE', $likePattern . '-%');
            })
            ->withTrashed()
            ->pluck('name')
            ->toArray();

        if (empty($existingNames)) {
            return $name;
        }

        if (!in_array($baseName, $existingNames)) {
            return $baseName;
        }

        // Find the highest suffix number
        $maxSuffix = 0;
        foreach ($existingNames as $existingName) {
            if ($existingName === $baseName) {
                $maxSuffix = max($maxSuffix, 0);
            } elseif (preg_match('/^' . preg_quote($baseName, '/') . '-(\d+)$/', $existingName, $matches)) {
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

        if (\setting('media_use_original_name_for_file_path')) {
            $slug = $name;
        } else {
            $slug = Str::slug($name, '-', ! FilamentMedia::turnOffAutomaticUrlTranslationIntoLatin() ? 'en' : false);
        }

        $index = 1;
        $baseSlug = $slug;

        while (File::exists(FilamentMedia::getRealPath(rtrim($folderPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $slug . '.' . $extension))) {
            $slug = $baseSlug . '-' . $index++;
        }

        if (empty($slug)) {
            $slug = 'file-' . time();
        }

        return Str::limit($slug, end: '') . '.' . $extension;
    }
}
