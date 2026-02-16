<?php

namespace Codenzia\FilamentMedia\Models;

use Codenzia\FilamentMedia\Database\Factories\MediaFolderFactory;
use Codenzia\FilamentMedia\Facades\FilamentMedia as RvMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Represents a hierarchical folder for organizing media files.
 * Supports nested parent-child relationships, tagging, and cascading soft deletes.
 */
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

    protected $casts = [];

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? strip_tags($value) : $value,
        );
    }

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
            $user = auth()->user();

            if (! $user) {
                return;
            }

            $scopeCallback = app(\Codenzia\FilamentMedia\FilamentMedia::class)->getMediaQueryScope();

            if ($scopeCallback) {
                call_user_func($scopeCallback, $query, $user);

                return;
            }

            if (RvMedia::canOnlyViewOwnMedia()) {
                $query->where('media_folders.user_id', $user->getAuthIdentifier());
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

    public function children(): HasMany
    {
        return $this->hasMany(MediaFolder::class, 'parent_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(MediaTag::class, 'media_folder_tag');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(MediaTag::class, 'media_folder_tag')
            ->where('media_tags.type', 'collection');
    }

    // ──────────────────────────────────────────────────
    // Query Scopes
    // ──────────────────────────────────────────────────

    public function scopeInParent(Builder $query, int|string|null $parentId): Builder
    {
        return $query->where('media_folders.parent_id', $parentId ?? 0);
    }

    public function scopeSorted(Builder $query, string $sortBy = 'name-asc'): Builder
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

        return $query->where('media_folders.name', 'LIKE', $term);
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

    // ──────────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────────

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

        // Reverse to get root-first order (always use forward slashes for Storage compatibility)
        $builtPath = implode('/', array_reverse($pathParts));

        if ($path) {
            return rtrim($builtPath, '/') . '/' . ltrim($path, '/');
        }

        return $builtPath;
    }

    // ──────────────────────────────────────────────────
    // Size Calculation
    // ──────────────────────────────────────────────────

    /**
     * Efficiently calculate recursive sizes for multiple folders at once.
     * Includes files from all descendant subfolders, not just direct children.
     * Uses only 2 queries regardless of tree depth.
     *
     * @param  array<int>  $folderIds
     * @return array<int, int> Map of folder ID => total size in bytes
     */
    public static function getRecursiveSizeMap(array $folderIds): array
    {
        if (empty($folderIds)) {
            return [];
        }

        // Load all folder ID/parent_id pairs to build tree in memory
        $allFolders = self::withoutGlobalScopes()
            ->select('id', 'parent_id')
            ->get()
            ->groupBy('parent_id');

        // For each folder, collect all descendant IDs (including self)
        $folderDescendants = [];
        $allDescendantIds = [];

        foreach ($folderIds as $folderId) {
            $descendants = [$folderId];
            $queue = [$folderId];

            while (! empty($queue)) {
                $currentId = array_shift($queue);
                $children = $allFolders->get($currentId, collect());

                foreach ($children as $child) {
                    $descendants[] = $child->id;
                    $queue[] = $child->id;
                }
            }

            $folderDescendants[$folderId] = $descendants;
            array_push($allDescendantIds, ...$descendants);
        }

        // Sum file sizes grouped by folder_id in a single query
        $fileSizes = MediaFile::withoutGlobalScopes()
            ->whereIn('folder_id', array_unique($allDescendantIds))
            ->selectRaw('folder_id, SUM(size) as total_size')
            ->groupBy('folder_id')
            ->pluck('total_size', 'folder_id');

        // Map sizes back to each original folder
        $result = [];
        foreach ($folderIds as $folderId) {
            $total = 0;
            foreach ($folderDescendants[$folderId] as $descendantId) {
                $total += (int) ($fileSizes->get($descendantId, 0));
            }
            $result[$folderId] = $total;
        }

        return $result;
    }

    // ──────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────

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
