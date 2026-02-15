<?php

use Codenzia\FilamentMedia\Services\StorageDriverService;

beforeEach(function () {
    $this->service = app(StorageDriverService::class);
});

describe('StorageDriverService - getMediaDriver', function () {
    it('returns the configured driver', function () {
        $driver = $this->service->getMediaDriver();

        expect($driver)->toBeString()
            ->and($driver)->toBe('public');
    });

    it('returns public as default when no driver is configured', function () {
        config()->set('media.disk', null);
        config()->set('filament-media.media.disk', null);

        $service = new StorageDriverService();
        $driver = $service->getMediaDriver();

        expect($driver)->toBe('public');
    });

    it('returns s3 when configured as s3', function () {
        config()->set('media.disk', 's3');

        $service = new StorageDriverService();
        $driver = $service->getMediaDriver();

        expect($driver)->toBe('s3');
    });
});

describe('StorageDriverService - isUsingCloud', function () {
    it('returns true for s3 driver', function () {
        config()->set('media.disk', 's3');

        $service = new StorageDriverService();

        expect($service->isUsingCloud())->toBeTrue();
    });

    it('returns true for r2 driver', function () {
        config()->set('media.disk', 'r2');

        $service = new StorageDriverService();

        expect($service->isUsingCloud())->toBeTrue();
    });

    it('returns true for do_spaces driver', function () {
        config()->set('media.disk', 'do_spaces');

        $service = new StorageDriverService();

        expect($service->isUsingCloud())->toBeTrue();
    });

    it('returns true for wasabi driver', function () {
        config()->set('media.disk', 'wasabi');

        $service = new StorageDriverService();

        expect($service->isUsingCloud())->toBeTrue();
    });

    it('returns true for bunnycdn driver', function () {
        config()->set('media.disk', 'bunnycdn');

        $service = new StorageDriverService();

        expect($service->isUsingCloud())->toBeTrue();
    });

    it('returns true for backblaze driver', function () {
        config()->set('media.disk', 'backblaze');

        $service = new StorageDriverService();

        expect($service->isUsingCloud())->toBeTrue();
    });

    it('returns false for public driver', function () {
        config()->set('media.disk', 'public');

        $service = new StorageDriverService();

        expect($service->isUsingCloud())->toBeFalse();
    });

    it('returns false for local driver', function () {
        config()->set('media.disk', 'local');

        $service = new StorageDriverService();

        expect($service->isUsingCloud())->toBeFalse();
    });
});

describe('StorageDriverService - getAvailableDrivers', function () {
    it('returns an array of available drivers', function () {
        $drivers = $this->service->getAvailableDrivers();

        expect($drivers)->toBeArray()
            ->and($drivers)->not->toBeEmpty();
    });

    it('contains expected driver keys', function () {
        $drivers = $this->service->getAvailableDrivers();

        expect($drivers)->toHaveKey('public')
            ->and($drivers)->toHaveKey('s3')
            ->and($drivers)->toHaveKey('r2')
            ->and($drivers)->toHaveKey('do_spaces')
            ->and($drivers)->toHaveKey('wasabi')
            ->and($drivers)->toHaveKey('bunnycdn')
            ->and($drivers)->toHaveKey('backblaze');
    });

    it('has human-readable labels as values', function () {
        $drivers = $this->service->getAvailableDrivers();

        expect($drivers['public'])->toBe('Local Disk')
            ->and($drivers['s3'])->toBe('Amazon S3')
            ->and($drivers['r2'])->toBe('Cloudflare R2')
            ->and($drivers['do_spaces'])->toBe('DigitalOcean Spaces');
    });

    it('returns exactly 7 drivers', function () {
        $drivers = $this->service->getAvailableDrivers();

        expect($drivers)->toHaveCount(7);
    });
});
