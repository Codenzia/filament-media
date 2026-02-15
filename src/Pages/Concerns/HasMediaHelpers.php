<?php

namespace Codenzia\FilamentMedia\Pages\Concerns;

use Filament\Notifications\Notification;

/**
 * Provides shared helper methods for media page components.
 *
 * Centralizes common operations like separating items by type
 * (files vs folders) and sending notifications to reduce
 * duplication across action and query traits.
 */
trait HasMediaHelpers
{
    /**
     * Separate an array of selected items into file IDs and folder IDs.
     *
     * @param  array<int, array{id: int, is_folder?: bool}>  $items
     * @return array{fileIds: int[], folderIds: int[]}
     */
    protected function separateItemsByType(array $items): array
    {
        $collection = collect($items);

        return [
            'fileIds' => $collection->filter(fn ($item) => ! ($item['is_folder'] ?? false))->pluck('id')->toArray(),
            'folderIds' => $collection->filter(fn ($item) => $item['is_folder'] ?? false)->pluck('id')->toArray(),
        ];
    }

    /**
     * Send a success notification using a translation key.
     *
     * @param  string  $key  Translation key relative to filament-media::media
     * @param  array<string, string>  $replace  Replacement values for the translation
     */
    protected function notifySuccess(string $key, array $replace = []): void
    {
        Notification::make()
            ->title(trans("filament-media::media.{$key}", $replace))
            ->success()
            ->send();
    }

    /**
     * Send an error notification using a translation key.
     *
     * @param  string  $key  Translation key relative to filament-media::media
     * @param  array<string, string>  $replace  Replacement values for the translation
     */
    protected function notifyError(string $key, array $replace = []): void
    {
        Notification::make()
            ->title(trans("filament-media::media.{$key}", $replace))
            ->danger()
            ->send();
    }

    /**
     * Send a warning notification using a translation key.
     *
     * @param  string  $key  Translation key relative to filament-media::media
     * @param  array<string, string>  $replace  Replacement values for the translation
     */
    protected function notifyWarning(string $key, array $replace = []): void
    {
        Notification::make()
            ->title(trans("filament-media::media.{$key}", $replace))
            ->warning()
            ->send();
    }
}
