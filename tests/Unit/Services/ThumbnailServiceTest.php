<?php

use Codenzia\FilamentMedia\Services\ThumbnailService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

describe('ThumbnailService', function () {
    it('can be instantiated', function () {
        $service = new ThumbnailService();

        expect($service)->toBeInstanceOf(ThumbnailService::class);
    });

    it('can set image path', function () {
        $service = new ThumbnailService();

        $result = $service->setImage('test/image.jpg');

        expect($result)->toBeInstanceOf(ThumbnailService::class);
    });

    it('can set size', function () {
        $service = new ThumbnailService();

        $result = $service->setSize(800, 600);

        expect($result)->toBeInstanceOf(ThumbnailService::class);
    });

    it('can set coordinates', function () {
        $service = new ThumbnailService();

        $result = $service->setCoordinates(100, 200);

        expect($result)->toBeInstanceOf(ThumbnailService::class);
    });

    it('can set destination path', function () {
        $service = new ThumbnailService();

        $result = $service->setDestinationPath('/path/to/destination');

        expect($result)->toBeInstanceOf(ThumbnailService::class);
    });

    it('can set file name', function () {
        $service = new ThumbnailService();

        $result = $service->setFileName('output.jpg');

        expect($result)->toBeInstanceOf(ThumbnailService::class);
    });

    it('returns fluent interface for chaining', function () {
        $service = new ThumbnailService();

        $result = $service
            ->setImage('test/image.jpg')
            ->setSize(100, 100)
            ->setCoordinates(0, 0)
            ->setDestinationPath('/path/to/output')
            ->setFileName('thumbnail.jpg');

        expect($result)->toBeInstanceOf(ThumbnailService::class);
    });
});

describe('ThumbnailService Validation', function () {
    it('throws exception when image is not set', function () {
        $service = new ThumbnailService();

        $service
            ->setSize(100, 100)
            ->setDestinationPath('/path')
            ->setFileName('file.jpg')
            ->save('crop');
    })->throws(InvalidArgumentException::class, 'Image source is required');

    it('throws exception when destination path is not set', function () {
        $service = new ThumbnailService();

        $service
            ->setImage('test/image.jpg')
            ->setSize(100, 100)
            ->setFileName('file.jpg')
            ->save('crop');
    })->throws(InvalidArgumentException::class, 'Destination path and filename are required');

    it('throws exception when file name is not set', function () {
        $service = new ThumbnailService();

        $service
            ->setImage('test/image.jpg')
            ->setSize(100, 100)
            ->setDestinationPath('/path')
            ->save('crop');
    })->throws(InvalidArgumentException::class, 'Destination path and filename are required');

    it('throws exception for crop with invalid dimensions', function () {
        // Create a simple valid JPEG image
        $img = imagecreatetruecolor(100, 100);
        $red = imagecolorallocate($img, 255, 0, 0);
        imagefill($img, 0, 0, $red);

        ob_start();
        imagejpeg($img, null, 90);
        $imageContent = ob_get_clean();
        imagedestroy($img);

        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, $imageContent);

        $service = new ThumbnailService();

        try {
            $service
                ->setImage($tempFile)
                ->setSize(0, 0)  // Invalid dimensions
                ->setDestinationPath(sys_get_temp_dir())
                ->setFileName('output.jpg')
                ->save('crop');
        } finally {
            @unlink($tempFile);
        }
    })->throws(InvalidArgumentException::class, 'Width and height must be positive for crop operation');
});

describe('ThumbnailService Image Processing', function () {
    it('can process real image file with crop', function () {
        // Create a simple valid JPEG image
        $img = imagecreatetruecolor(200, 200);
        $red = imagecolorallocate($img, 255, 0, 0);
        imagefill($img, 0, 0, $red);

        ob_start();
        imagejpeg($img, null, 90);
        $imageContent = ob_get_clean();
        imagedestroy($img);

        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir . '/test_source_' . uniqid() . '.jpg';
        file_put_contents($tempFile, $imageContent);

        $service = new ThumbnailService();

        $result = $service
            ->setImage($tempFile)
            ->setSize(50, 50)
            ->setCoordinates(0, 0)
            ->setDestinationPath($tempDir)
            ->setFileName('test_output_' . uniqid() . '.jpg')
            ->save('crop');

        expect($result)->toBeString()
            ->and(file_exists($result))->toBeTrue();

        // Cleanup
        @unlink($tempFile);
        @unlink($result);
    });

    it('can process real image file with resize', function () {
        // Create a simple valid JPEG image
        $img = imagecreatetruecolor(200, 200);
        $blue = imagecolorallocate($img, 0, 0, 255);
        imagefill($img, 0, 0, $blue);

        ob_start();
        imagejpeg($img, null, 90);
        $imageContent = ob_get_clean();
        imagedestroy($img);

        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir . '/test_resize_source_' . uniqid() . '.jpg';
        file_put_contents($tempFile, $imageContent);

        $service = new ThumbnailService();

        $result = $service
            ->setImage($tempFile)
            ->setSize(100, 100)
            ->setDestinationPath($tempDir)
            ->setFileName('test_resize_output_' . uniqid() . '.jpg')
            ->save('resize');

        expect($result)->toBeString()
            ->and(file_exists($result))->toBeTrue();

        // Cleanup
        @unlink($tempFile);
        @unlink($result);
    });

    it('can generate thumbnail using helper method', function () {
        // Create a simple valid JPEG image
        $img = imagecreatetruecolor(300, 300);
        $green = imagecolorallocate($img, 0, 255, 0);
        imagefill($img, 0, 0, $green);

        ob_start();
        imagejpeg($img, null, 90);
        $imageContent = ob_get_clean();
        imagedestroy($img);

        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir . '/test_thumb_source_' . uniqid() . '.jpg';
        file_put_contents($tempFile, $imageContent);

        $destFile = $tempDir . '/test_thumb_output_' . uniqid() . '.jpg';

        $service = new ThumbnailService();
        $result = $service->generateThumbnail($tempFile, '150x150', $destFile);

        expect($result)->toBeString()
            ->and(file_exists($result))->toBeTrue();

        // Cleanup
        @unlink($tempFile);
        @unlink($result);
    });
});
