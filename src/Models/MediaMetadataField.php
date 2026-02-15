<?php

namespace Codenzia\FilamentMedia\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Defines a custom metadata field that can be attached to media files.
 */
class MediaMetadataField extends Model
{
    protected $table = 'media_metadata_fields';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'options',
        'is_required',
        'is_searchable',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'json',
        'is_required' => 'boolean',
        'is_searchable' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(MediaFile::class, 'media_file_metadata')
            ->withPivot('value')
            ->withTimestamps();
    }

    public function scopeSearchable(Builder $query): Builder
    {
        return $query->where('is_searchable', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    public static function getSearchableFields(): \Illuminate\Support\Collection
    {
        return self::searchable()->ordered()->get();
    }

    protected static function booted(): void
    {
        static::creating(function (self $field): void {
            if (empty($field->slug)) {
                $field->slug = Str::slug($field->name);
            }
        });
    }
}
