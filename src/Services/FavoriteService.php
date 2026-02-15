<?php

namespace Codenzia\FilamentMedia\Services;

use Codenzia\FilamentMedia\Models\MediaSetting;

/**
 * Manages per-user favorite and recently accessed media items.
 */
class FavoriteService
{
    public function addToFavorites(array $items, int $userId): void
    {
        $meta = MediaSetting::query()->firstOrCreate([
            'key' => 'favorites',
            'user_id' => $userId,
        ]);

        $meta->value = ! empty($meta->value)
            ? array_merge($meta->value, $items)
            : $items;

        $meta->save();
    }

    public function removeFromFavorites(array $items, int $userId): void
    {
        $meta = MediaSetting::query()->firstOrCreate([
            'key' => 'favorites',
            'user_id' => $userId,
        ]);

        if (empty($meta->value)) {
            return;
        }

        $value = $meta->value;

        foreach ($value as $key => $existing) {
            foreach ($items as $selected) {
                if (
                    ($existing['is_folder'] ?? false) == ($selected['is_folder'] ?? false)
                    && $existing['id'] == $selected['id']
                ) {
                    unset($value[$key]);
                }
            }
        }

        $meta->value = array_values($value);
        $meta->save();
    }

    public function getFavorites(int $userId): array
    {
        $meta = MediaSetting::query()
            ->where('key', 'favorites')
            ->where('user_id', $userId)
            ->first();

        return $meta?->value ?? [];
    }

    public function isFavorited(int $id, bool $isFolder, int $userId): bool
    {
        $favorites = $this->getFavorites($userId);

        foreach ($favorites as $item) {
            if ($item['id'] == $id && ($item['is_folder'] ?? false) == $isFolder) {
                return true;
            }
        }

        return false;
    }

    public function addToRecent(array $item, int $userId): void
    {
        if (empty($item['id'])) {
            return;
        }

        $meta = MediaSetting::query()->firstOrCreate([
            'key' => 'recent_items',
            'user_id' => $userId,
        ]);

        $value = $meta->value ?: [];

        $recentItem = [
            'id' => $item['id'],
            'is_folder' => (bool) ($item['is_folder'] ?? false),
        ];

        // Remove existing entry so it can be moved to the top
        foreach ($value as $key => $existing) {
            if (
                $existing['id'] == $recentItem['id']
                && isset($existing['is_folder'])
                && $existing['is_folder'] == $recentItem['is_folder']
            ) {
                unset($value[$key]);
                break;
            }
        }

        array_unshift($value, $recentItem);

        // Keep only the 20 most recent
        if (count($value) > 20) {
            $value = array_slice($value, 0, 20);
        }

        $meta->value = array_values($value);
        $meta->save();
    }

    public function getRecentItems(int $userId): array
    {
        $meta = MediaSetting::query()
            ->where('key', 'recent_items')
            ->where('user_id', $userId)
            ->first();

        return $meta?->value ?? [];
    }
}
