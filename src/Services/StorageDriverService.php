<?php

namespace Codenzia\FilamentMedia\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class StorageDriverService
{
    public function getMediaDriver(): string
    {
        return $this->getConfig('disk') ?? $this->getConfig('driver') ?? 'public';
    }

    public function isUsingCloud(): bool
    {
        return ! in_array($this->getMediaDriver(), ['local', 'public']);
    }

    public function getAvailableDrivers(): array
    {
        return [
            'public' => 'Local Disk',
            's3' => 'Amazon S3',
            'r2' => 'Cloudflare R2',
            'do_spaces' => 'DigitalOcean Spaces',
            'wasabi' => 'Wasabi',
            'bunnycdn' => 'BunnyCDN',
            'backblaze' => 'Backblaze B2',
        ];
    }

    public function configureDisk(string $driver, array $config): void
    {
        $diskConfig = match ($driver) {
            's3' => $this->buildS3Config($config),
            'r2' => $this->buildR2Config($config),
            'do_spaces' => $this->buildDoSpacesConfig($config),
            'wasabi' => $this->buildWasabiConfig($config),
            'bunnycdn' => $this->buildBunnyCdnConfig($config),
            'backblaze' => $this->buildBackblazeConfig($config),
            default => null,
        };

        if ($diskConfig) {
            config()->set("filesystems.disks.{$driver}", $diskConfig);
        }
    }

    public function setUploadPathAndURLToPublic(): void
    {
        config([
            'filesystems.disks.public.root' => $this->getUploadPath(),
            'filesystems.disks.public.url' => $this->getUploadURL(),
        ]);
    }

    public function getUploadPath(): string
    {
        $customFolder = $this->getConfig('default_upload_folder');

        if (\setting('media_customize_upload_path')) {
            $customFolder = trim(\setting('media_upload_path'), '/');
        }

        if ($customFolder) {
            return public_path($customFolder);
        }

        return is_link(public_path('storage')) ? storage_path('app/public') : public_path('storage');
    }

    public function getUploadURL(): string
    {
        $uploadUrl = $this->getConfig('default_upload_url') ?: asset('storage');

        if (\setting('media_customize_upload_path')) {
            $uploadUrl = trim(asset(\setting('media_upload_path')), '/');
        }

        return str_replace('/index.php', '', $uploadUrl);
    }

    public function getCustomS3Path(): string
    {
        $customPath = trim(\setting('media_s3_path', $this->getConfig('custom_s3_path')), '/');

        return $customPath ? $customPath . '/' : '';
    }

    protected function getConfig(?string $key = null, mixed $default = null): mixed
    {
        $configs = config('filament-media.media') ?? config('media');

        if (! $key) {
            return $configs;
        }

        return Arr::get($configs, $key, $default);
    }

    protected function buildS3Config(array $config): ?array
    {
        if (! $config['key'] || ! $config['secret'] || ! $config['region'] || ! $config['bucket'] || ! $config['url']) {
            return null;
        }

        return [
            'driver' => 's3',
            'visibility' => 'public',
            'throw' => true,
            'key' => $config['key'],
            'secret' => $config['secret'],
            'region' => $config['region'],
            'bucket' => $config['bucket'],
            'url' => $config['url'],
            'endpoint' => $config['endpoint'] ?? null,
            'use_path_style_endpoint' => (bool) ($config['use_path_style_endpoint'] ?? false),
        ];
    }

    protected function buildR2Config(array $config): ?array
    {
        if (! $config['key'] || ! $config['secret'] || ! $config['bucket'] || ! $config['endpoint']) {
            return null;
        }

        return [
            'driver' => 's3',
            'visibility' => 'public',
            'throw' => true,
            'key' => $config['key'],
            'secret' => $config['secret'],
            'region' => 'auto',
            'bucket' => $config['bucket'],
            'url' => $config['url'] ?? null,
            'endpoint' => $config['endpoint'],
            'use_path_style_endpoint' => (bool) ($config['use_path_style_endpoint'] ?? false),
        ];
    }

    protected function buildDoSpacesConfig(array $config): ?array
    {
        if (! $config['key'] || ! $config['secret'] || ! $config['region'] || ! $config['bucket'] || ! $config['endpoint']) {
            return null;
        }

        return [
            'driver' => 's3',
            'visibility' => 'public',
            'throw' => true,
            'key' => $config['key'],
            'secret' => $config['secret'],
            'region' => $config['region'],
            'bucket' => $config['bucket'],
            'endpoint' => $config['endpoint'],
            'use_path_style_endpoint' => (bool) ($config['use_path_style_endpoint'] ?? false),
        ];
    }

    protected function buildWasabiConfig(array $config): ?array
    {
        if (! $config['key'] || ! $config['secret'] || ! $config['region'] || ! $config['bucket']) {
            return null;
        }

        return [
            'driver' => 'wasabi',
            'visibility' => 'public',
            'throw' => true,
            'key' => $config['key'],
            'secret' => $config['secret'],
            'region' => $config['region'],
            'bucket' => $config['bucket'],
            'root' => $config['root'] ?? '/',
        ];
    }

    protected function buildBunnyCdnConfig(array $config): ?array
    {
        if (! $config['hostname'] || ! $config['storage_zone'] || ! $config['api_key']) {
            return null;
        }

        return [
            'driver' => 'bunnycdn',
            'visibility' => 'public',
            'throw' => true,
            'hostname' => $config['hostname'],
            'storage_zone' => $config['storage_zone'],
            'api_key' => $config['api_key'],
            'region' => $config['region'] ?? null,
        ];
    }

    protected function buildBackblazeConfig(array $config): ?array
    {
        if (! $config['key'] || ! $config['secret'] || ! $config['region'] || ! $config['bucket'] || ! $config['endpoint']) {
            return null;
        }

        return [
            'driver' => 's3',
            'visibility' => 'public',
            'throw' => true,
            'key' => $config['key'],
            'secret' => $config['secret'],
            'region' => $config['region'],
            'bucket' => $config['bucket'],
            'url' => $config['url'] ?? null,
            'endpoint' => str_starts_with($config['endpoint'], 'https://')
                ? $config['endpoint']
                : 'https://' . $config['endpoint'],
            'use_path_style_endpoint' => (bool) ($config['use_path_style_endpoint'] ?? false),
            'options' => ['StorageClass' => 'STANDARD'],
            'request_checksum_calculation' => 'when_required',
            'response_checksum_validation' => 'when_required',
        ];
    }
}
