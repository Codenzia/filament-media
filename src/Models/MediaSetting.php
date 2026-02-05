<?php

namespace Codenzia\FilamentMedia\Models;

use Codenzia\FilamentMedia\Database\Factories\MediaSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaSetting extends BaseModel
{
    use HasFactory;

    protected static function newFactory(): MediaSettingFactory
    {
        return MediaSettingFactory::new();
    }

    protected $table = 'media_settings';

    protected $fillable = [
        'key',
        'value',
        'user_id',
        'media_id',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    /**
     * Get the user this setting belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'user_id');
    }

    /**
     * Get the media file this setting is associated with.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'media_id');
    }

    /**
     * Get a setting value for a user.
     */
    public static function getValue(string $key, ?int $userId = null, mixed $default = null): mixed
    {
        $query = static::where('key', $key);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $setting = $query->first();

        return $setting?->value ?? $default;
    }

    /**
     * Set a setting value for a user.
     */
    public static function setValue(string $key, mixed $value, ?int $userId = null): static
    {
        $attributes = ['key' => $key];

        if ($userId) {
            $attributes['user_id'] = $userId;
        }

        return static::updateOrCreate($attributes, ['value' => $value]);
    }
}
