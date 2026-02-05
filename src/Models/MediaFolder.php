<?php

namespace Codenzia\FilamentMedia\Models;

use Codenzia\FilamentMedia\Database\Factories\MediaFolderFactory;
use Codenzia\FilamentMedia\Facades\FilamentMedia as RvMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Codenzia\FilamentMedia\Services\SafeContentService;

class MediaFolder extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected static function newFactory(): MediaFolderFactory
    {
        return MediaFolderFactory::new();
    }

    protected $table = 'media_folders';

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'user_id',
        'color',
    ];

    protected $casts = [
        'name' => SafeContentService::class,
    ];

    protected static function booted(): void
    {
        static::deleted(function (MediaFolder $folder): void {
            if ($folder->isForceDeleting()) {
                $folder->files()->withTrashed()->each(fn (MediaFile $file) => $file->forceDelete());

                if (Storage::directoryExists($folder->slug)) {
                    Storage::deleteDirectory($folder->slug);
                }
            } else {
                $folder->files()->withTrashed()->each(fn (MediaFile $file) => $file->delete());
            }
        });

        static::restoring(function (MediaFolder $folder): void {
            $folder->files()->withTrashed()->each(fn (MediaFile $file) => $file->restore());
        });

        static::addGlobalScope('ownMedia', function (Builder $query): void {
            if (RvMedia::canOnlyViewOwnMedia()) {
                $query->where('media_folders.user_id', auth()->id());
            }
        });
    }

    public function files(): HasMany
    {
        return $this->hasMany(MediaFile::class, 'folder_id', 'id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'parent_id')->withDefault();
    }

    /**
     * Get the user who owns this folder.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'user_id');
    }

    /**
     * Get the user who created this folder.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'created_by_user_id');
    }

    /**
     * Get the user who last updated this folder.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'updated_by_user_id');
    }

    /**
     * Get the parent model that this folder is attached to.
     */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get child folders.
     */
    public function children(): HasMany
    {
        return $this->hasMany(MediaFolder::class, 'parent_id');
    }

    /**
     * Get all parent folders.
     * Optimized to fetch all folders once and traverse in memory.
     */
    protected function parents(): Attribute
    {
        return Attribute::get(function (): Collection {
            if (!$this->parent_id) {
                return collect();
            }

            // Fetch all folders in a single query
            $allFolders = self::query()
                ->select(['id', 'name', 'slug', 'parent_id'])
                ->withTrashed()
                ->get()
                ->keyBy('id');

            $parents = collect();
            $currentParentId = $this->parent_id;
            $maxDepth = 50; // Prevent infinite loops
            $depth = 0;

            while ($currentParentId && $depth < $maxDepth) {
                $parent = $allFolders->get($currentParentId);
                if (!$parent || !$parent->id) {
                    break;
                }
                $parents->push($parent);
                $currentParentId = $parent->parent_id;
                $depth++;
            }

            return $parents;
        });
    }

    /**
     * Get the full path for a folder.
     * Optimized to fetch all folders once and build path in memory.
     */
    public static function getFullPath(int|string|null $folderId, ?string $path = ''): ?string
    {
        if (!$folderId) {
            return $path;
        }

        // Fetch all folders in a single query
        $allFolders = self::query()
            ->select(['id', 'slug', 'parent_id'])
            ->withTrashed()
            ->get()
            ->keyBy('id');

        // Build path by traversing ancestors
        $pathParts = [];
        $currentId = $folderId;
        $maxDepth = 50; // Prevent infinite loops
        $depth = 0;

        while ($currentId && $depth < $maxDepth) {
            $folder = $allFolders->get($currentId);
            if (!$folder) {
                break;
            }
            $pathParts[] = $folder->slug;
            $currentId = $folder->parent_id;
            $depth++;
        }

        if (empty($pathParts)) {
            return $path;
        }

        // Reverse to get root-first order
        $builtPath = implode(DIRECTORY_SEPARATOR, array_reverse($pathParts));

        if ($path) {
            return rtrim($builtPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
        }

        return $builtPath;
    }

    /**
     * Create a unique slug for a folder.
     * Optimized to use a single query to find all existing slugs.
     */
    public static function createSlug(string $name, int|string|null $parentId): string
    {
        $slug = Str::slug($name, '-', !RvMedia::turnOffAutomaticUrlTranslationIntoLatin() ? 'en' : false);
        $baseSlug = $slug;
        $likePattern = str_replace(['%', '_'], ['\%', '\_'], $baseSlug);

        // Get all existing slugs that match the pattern
        $existingSlugs = self::query()
            ->where('parent_id', $parentId)
            ->where(function ($query) use ($likePattern, $baseSlug) {
                $query->where('slug', $baseSlug)
                    ->orWhere('slug', 'LIKE', $likePattern . '-%');
            })
            ->withTrashed()
            ->pluck('slug')
            ->toArray();

        if (empty($existingSlugs) || !in_array($baseSlug, $existingSlugs)) {
            return $slug;
        }

        // Find the highest suffix number
        $maxSuffix = 0;
        foreach ($existingSlugs as $existingSlug) {
            if ($existingSlug === $baseSlug) {
                $maxSuffix = max($maxSuffix, 0);
            } elseif (preg_match('/^' . preg_quote($baseSlug, '/') . '-(\d+)$/', $existingSlug, $matches)) {
                $maxSuffix = max($maxSuffix, (int) $matches[1]);
            }
        }

        return $baseSlug . '-' . ($maxSuffix + 1);
    }

    /**
     * Create a unique name for a folder.
     * Optimized to use a single query to find all existing names.
     */
    public static function createName(string $name, int|string|null $parentId): string
    {
        $baseName = $name;
        $likePattern = str_replace(['%', '_'], ['\%', '\_'], $baseName);

        // Get all existing names that match the pattern
        $existingNames = self::query()
            ->where('parent_id', $parentId)
            ->where(function ($query) use ($likePattern, $baseName) {
                $query->where('name', $baseName)
                    ->orWhere('name', 'LIKE', $likePattern . '-%');
            })
            ->withTrashed()
            ->pluck('name')
            ->toArray();

        if (empty($existingNames) || !in_array($baseName, $existingNames)) {
            return $name;
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
}
