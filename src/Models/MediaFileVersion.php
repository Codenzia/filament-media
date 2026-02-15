<?php

namespace Codenzia\FilamentMedia\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a historical version of a media file, tracking changes over time.
 */
class MediaFileVersion extends Model
{
    public $timestamps = false;

    protected $table = 'media_file_versions';

    protected $fillable = [
        'media_file_id',
        'version_number',
        'url',
        'size',
        'mime_type',
        'user_id',
        'changelog',
    ];

    protected $casts = [
        'size' => 'integer',
        'version_number' => 'integer',
        'created_at' => 'datetime',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'media_file_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'user_id');
    }

    public function scopeForFile(Builder $query, int $fileId): Builder
    {
        return $query->where('media_file_id', $fileId);
    }

    public function scopeLatestVersion(Builder $query): Builder
    {
        return $query->orderByDesc('version_number');
    }
}
