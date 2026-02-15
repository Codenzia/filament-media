<?php

use Codenzia\FilamentMedia\Models\MediaSetting;
use Codenzia\FilamentMedia\Services\FavoriteService;

beforeEach(function () {
    $this->service = app(FavoriteService::class);
    $this->userId = 1;
});

describe('FavoriteService - addToFavorites', function () {
    it('adds items to favorites', function () {
        $items = [
            ['id' => 1, 'is_folder' => false],
            ['id' => 2, 'is_folder' => true],
        ];

        $this->service->addToFavorites($items, $this->userId);

        $favorites = $this->service->getFavorites($this->userId);

        expect($favorites)->toHaveCount(2)
            ->and($favorites[0]['id'])->toBe(1)
            ->and($favorites[1]['id'])->toBe(2)
            ->and($favorites[1]['is_folder'])->toBeTrue();
    });

    it('appends to existing favorites', function () {
        $this->service->addToFavorites([
            ['id' => 1, 'is_folder' => false],
        ], $this->userId);

        $this->service->addToFavorites([
            ['id' => 5, 'is_folder' => true],
        ], $this->userId);

        $favorites = $this->service->getFavorites($this->userId);

        expect($favorites)->toHaveCount(2);
    });

    it('stores favorites in media_settings table', function () {
        $this->service->addToFavorites([
            ['id' => 10, 'is_folder' => false],
        ], $this->userId);

        $setting = MediaSetting::where('key', 'favorites')
            ->where('user_id', $this->userId)
            ->first();

        expect($setting)->not->toBeNull()
            ->and($setting->value)->toBeArray()
            ->and($setting->value)->toHaveCount(1);
    });
});

describe('FavoriteService - removeFromFavorites', function () {
    it('removes items from favorites', function () {
        $this->service->addToFavorites([
            ['id' => 1, 'is_folder' => false],
            ['id' => 2, 'is_folder' => true],
            ['id' => 3, 'is_folder' => false],
        ], $this->userId);

        $this->service->removeFromFavorites([
            ['id' => 2, 'is_folder' => true],
        ], $this->userId);

        $favorites = $this->service->getFavorites($this->userId);

        expect($favorites)->toHaveCount(2)
            ->and(collect($favorites)->pluck('id')->toArray())->toBe([1, 3]);
    });

    it('does nothing when removing from empty favorites', function () {
        $this->service->removeFromFavorites([
            ['id' => 1, 'is_folder' => false],
        ], $this->userId);

        $favorites = $this->service->getFavorites($this->userId);

        expect($favorites)->toBeArray();
    });

    it('distinguishes between files and folders with same id', function () {
        $this->service->addToFavorites([
            ['id' => 1, 'is_folder' => false],
            ['id' => 1, 'is_folder' => true],
        ], $this->userId);

        $this->service->removeFromFavorites([
            ['id' => 1, 'is_folder' => true],
        ], $this->userId);

        $favorites = $this->service->getFavorites($this->userId);

        expect($favorites)->toHaveCount(1)
            ->and($favorites[0]['is_folder'] ?? false)->toBeFalse();
    });
});

describe('FavoriteService - getFavorites', function () {
    it('returns empty array when no favorites exist', function () {
        $favorites = $this->service->getFavorites($this->userId);

        expect($favorites)->toBe([]);
    });

    it('returns favorites for the correct user', function () {
        $this->service->addToFavorites([
            ['id' => 1, 'is_folder' => false],
        ], 1);

        $this->service->addToFavorites([
            ['id' => 2, 'is_folder' => false],
        ], 2);

        $favoritesUser1 = $this->service->getFavorites(1);
        $favoritesUser2 = $this->service->getFavorites(2);

        expect($favoritesUser1)->toHaveCount(1)
            ->and($favoritesUser1[0]['id'])->toBe(1)
            ->and($favoritesUser2)->toHaveCount(1)
            ->and($favoritesUser2[0]['id'])->toBe(2);
    });
});

describe('FavoriteService - isFavorited', function () {
    it('returns true when item is favorited', function () {
        $this->service->addToFavorites([
            ['id' => 5, 'is_folder' => false],
        ], $this->userId);

        $result = $this->service->isFavorited(5, false, $this->userId);

        expect($result)->toBeTrue();
    });

    it('returns false when item is not favorited', function () {
        $result = $this->service->isFavorited(99, false, $this->userId);

        expect($result)->toBeFalse();
    });

    it('returns false when same id exists but is_folder differs', function () {
        $this->service->addToFavorites([
            ['id' => 3, 'is_folder' => true],
        ], $this->userId);

        $result = $this->service->isFavorited(3, false, $this->userId);

        expect($result)->toBeFalse();
    });

    it('returns true for favorited folder', function () {
        $this->service->addToFavorites([
            ['id' => 7, 'is_folder' => true],
        ], $this->userId);

        $result = $this->service->isFavorited(7, true, $this->userId);

        expect($result)->toBeTrue();
    });
});

describe('FavoriteService - addToRecent', function () {
    it('adds an item to the recent list', function () {
        $this->service->addToRecent(['id' => 1, 'is_folder' => false], $this->userId);

        $recent = $this->service->getRecentItems($this->userId);

        expect($recent)->toHaveCount(1)
            ->and($recent[0]['id'])->toBe(1);
    });

    it('places newest item at the top', function () {
        $this->service->addToRecent(['id' => 1, 'is_folder' => false], $this->userId);
        $this->service->addToRecent(['id' => 2, 'is_folder' => false], $this->userId);
        $this->service->addToRecent(['id' => 3, 'is_folder' => false], $this->userId);

        $recent = $this->service->getRecentItems($this->userId);

        expect($recent[0]['id'])->toBe(3)
            ->and($recent[1]['id'])->toBe(2)
            ->and($recent[2]['id'])->toBe(1);
    });

    it('moves duplicate item to the top instead of adding again', function () {
        $this->service->addToRecent(['id' => 1, 'is_folder' => false], $this->userId);
        $this->service->addToRecent(['id' => 2, 'is_folder' => false], $this->userId);
        $this->service->addToRecent(['id' => 1, 'is_folder' => false], $this->userId);

        $recent = $this->service->getRecentItems($this->userId);

        expect($recent)->toHaveCount(2)
            ->and($recent[0]['id'])->toBe(1)
            ->and($recent[1]['id'])->toBe(2);
    });

    it('does nothing when item has no id', function () {
        $this->service->addToRecent(['is_folder' => false], $this->userId);

        $recent = $this->service->getRecentItems($this->userId);

        expect($recent)->toBe([]);
    });
});

describe('FavoriteService - getRecentItems', function () {
    it('returns empty array when no recent items exist', function () {
        $recent = $this->service->getRecentItems($this->userId);

        expect($recent)->toBe([]);
    });

    it('limits recent items to a maximum of 20', function () {
        for ($i = 1; $i <= 25; $i++) {
            $this->service->addToRecent(['id' => $i, 'is_folder' => false], $this->userId);
        }

        $recent = $this->service->getRecentItems($this->userId);

        expect($recent)->toHaveCount(20)
            ->and($recent[0]['id'])->toBe(25)
            ->and($recent[19]['id'])->toBe(6);
    });
});
