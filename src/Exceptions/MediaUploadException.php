<?php

declare(strict_types=1);

namespace Codenzia\FilamentMedia\Exceptions;

/**
 * Thrown when a media upload or download operation fails.
 * Replaces the old array-based error returns from UploadService.
 */
class MediaUploadException extends \RuntimeException
{
    public static function noFileDetected(): static
    {
        return new static(trans('filament-media::media.can_not_detect_file_type'));
    }

    public static function invalidFileType(?string $allowedTypes = null): static
    {
        if ($allowedTypes) {
            return new static(trans('filament-media::media.validation.uploaded_file_invalid_type_with_list', [
                'types' => $allowedTypes,
            ]));
        }

        return new static(trans('filament-media::media.validation.uploaded_file_invalid_type'));
    }

    public static function validationFailed(string $message): static
    {
        return new static($message);
    }

    public static function fileTooLarge(string $humanSize): static
    {
        return new static(trans('filament-media::media.file_too_big_readable_size', ['size' => $humanSize]));
    }

    public static function unableToWrite(string $folder): static
    {
        return new static(trans('filament-media::media.unable_to_write', ['folder' => $folder]));
    }

    public static function networkError(string $url, string $reason = ''): static
    {
        $message = $reason ?: trans('filament-media::media.unable_download_image_from', ['url' => $url]);

        return new static($message);
    }

    public static function invalidUrl(?string $url = null): static
    {
        return new static(trans('filament-media::media.url_invalid'));
    }

    public static function invalidPath(): static
    {
        return new static(trans('filament-media::media.path_invalid'));
    }

    public static function ssrfBlocked(string $message): static
    {
        return new static($message);
    }

    public static function tempFileError(): static
    {
        return new static(trans('filament-media::media.unable_to_create_temp_file'));
    }
}
