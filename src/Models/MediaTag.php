<?php

namespace Codenzia\FilamentMedia\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Represents a tag or collection used to categorize media files and folders.
 * Supports hierarchical parent-child relationships.
 */
class MediaTag extends Model
{
    protected $table = 'media_tags';

    protected $fillable = [
        'name',
        'slug',
        'color',
        'type',
        'description',
        'parent_id',
        'user_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $tag): void {
            if (empty($tag->slug)) {
                $tag->slug = self::createSlug($tag->name);
            }

            if (empty($tag->type)) {
                $tag->type = 'tag';
            }
        });
    }

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(MediaFile::class, 'media_file_tag');
    }

    public function folders(): BelongsToMany
    {
        return $this->belongsToMany(MediaFolder::class, 'media_folder_tag');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'user_id');
    }

    public function scopeTags(Builder $query): Builder
    {
        return $query->where('type', 'tag');
    }

    public function scopeCollections(Builder $query): Builder
    {
        return $query->where('type', 'collection');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public static function findOrCreateByName(string $name, string $type = 'tag'): self
    {
        $slug = Str::slug($name);

        return self::firstOrCreate(
            ['slug' => $slug, 'type' => $type],
            ['name' => $name, 'slug' => $slug, 'type' => $type]
        );
    }

    public static function createSlug(string $name): string
    {
        $slug = Str::slug($name);
        $baseSlug = $slug;
        $counter = 1;

        while (self::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        return $slug;
    }
}
