<?php

namespace Codenzia\FilamentMedia\Forms;

use Codenzia\FilamentMedia\Services\StorageDriverService;
use Codenzia\FilamentMedia\Services\UploadService;
use Filament\Forms\Components\FileUpload;
use Symfony\Component\Mime\MimeTypes;

/**
 * Pre-configured FileUpload component that reads allowed types, max size,
 * and storage disk from the filament-media plugin configuration.
 */
class MediaFileUpload
{
    public static function make(?string $dir = null): FileUpload
    {
        $uploadService = app(UploadService::class);
        $storageService = app(StorageDriverService::class);

        // Convert extension list (e.g. ['jpg','png','pdf']) to MIME types (e.g. ['image/jpeg','image/png','application/pdf'])
        $mimeTypes = MimeTypes::getDefault();
        $acceptedTypes = [];
        foreach ($uploadService->getAllowedMimeTypes() as $ext) {
            $resolved = $mimeTypes->getMimeTypes(trim($ext));
            if (! empty($resolved)) {
                array_push($acceptedTypes, ...$resolved);
            }
        }
        $acceptedTypes = array_values(array_unique($acceptedTypes));

        // respect admin settings + server limits; Filament expects KB
        $maxSizeKb = (int) ($uploadService->getMaxSize() / 1024);

        return FileUpload::make('url')
            ->label(__('File'))
            ->disk($storageService->getMediaDriver())
            ->directory($dir)
            ->openable()
            ->downloadable()
            ->preserveFilenames()
            ->maxSize($maxSizeKb)
            ->acceptedFileTypes($acceptedTypes);
    }
}
