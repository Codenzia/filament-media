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
        return '<i class="' . $icon . '"></i>';
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
}
