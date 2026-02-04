<?php

namespace Codenzia\FilamentMedia\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Blade;

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
        return Blade::render('<x-filament::icon icon="' . $icon . '"></x-filament::icon>');
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

    public function stringify($content): ?string
    {
        if (empty($content)) {
            return null;
        }

        if (is_string($content) || is_numeric($content) || is_bool($content)) {
            return $content;
        }

        if (is_array($content)) {
            return json_encode($content);
        }

        return null;
    }

}
