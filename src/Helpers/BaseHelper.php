<?php

namespace Codenzia\FilamentMedia\Helpers;

use Illuminate\Support\Facades\File;

class BaseHelper
{
    public static function humanFilesize(int|float $bytes, int $precision = 2): string
    {
        $units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public static function clean(?string $content): string
    {
        return $content ? clean($content) : '';
    }

    public static function renderIcon(string $icon): string
    {
        return '<x-filament::icon icon="' . $icon . '"></x-filament::icon>';
    }

    public static function logError(\Throwable $exception): void
    {
        logger()->error($exception->getMessage(), [
            'exception' => $exception,
        ]);
    }

    public static function getAdminMasterLayoutTemplate(): string
    {
        return 'filament::components.layout.index';
    }

    public static function formatDate($date, $format = 'Y-m-d H:i:s'): string
    {
        return $date->format($format);
    }

    /**
     * Convert content to a safe string representation.
     * All output is HTML-escaped to prevent XSS attacks.
     */
    public function stringify(mixed $content): ?string
    {
        // Handle booleans first before empty() check
        if (is_bool($content)) {
            return $content ? '1' : '0';
        }

        if (empty($content) && $content !== '0' && $content !== 0) {
            return null;
        }

        if (is_string($content) || is_numeric($content)) {
            return htmlspecialchars((string) $content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if (is_array($content)) {
            return htmlspecialchars(json_encode($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return null;
    }

}
