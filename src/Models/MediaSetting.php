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

    /**
     * Scope to system-wide settings (no user or media association).
     */
    public function scopeSystem($query)
    {
        return $query->whereNull('user_id')->whereNull('media_id');
    }

    /**
     * Get a system-wide setting value.
     *
     * @param string $key The setting key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public static function getSystemSetting(string $key, mixed $default = null): mixed
    {
        $setting = static::system()
            ->where('key', $key)
            ->first();

        return $setting?->value ?? $default;
    }

    /**
     * Set a system-wide setting value.
     *
     * @param string $key The setting key
     * @param mixed $value The value to store
     * @return static
     */
    public static function setSystemSetting(string $key, mixed $value): static
    {
        return static::updateOrCreate(
            [
                'key' => $key,
                'user_id' => null,
                'media_id' => null,
            ],
            ['value' => $value]
        );
    }

    /**
     * Get all system settings as an array.
     *
     * @return array
     */
    public static function getAllSystemSettings(): array
    {
        return static::system()
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Set multiple system settings at once.
     *
     * @param array $settings Key-value pairs
     * @return void
     */
    public static function setSystemSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            static::setSystemSetting($key, $value);
        }
    }
}
