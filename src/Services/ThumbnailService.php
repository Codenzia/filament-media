<?php

namespace Codenzia\FilamentMedia\Services;

use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;

/**
 * Crops and resizes images to generate thumbnails using Imagick or GD,
 * with support for both local and cloud storage.
 */
class ThumbnailService
{
    protected string $image;
    protected int $width = 0;
    protected int $height = 0;
    protected int $x = 0;
    protected int $y = 0;
    protected string $destinationPath = '';
    protected string $fileName = '';

    public function setImage(string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function setSize(int $width, int $height): static
    {
        $this->width = $width;
        $this->height = $height;
        return $this;
    }

    public function setCoordinates(int $x, int $y): static
    {
        $this->x = $x;
        $this->y = $y;
        return $this;
    }

    public function setDestinationPath(string $path): static
    {
        $this->destinationPath = $path;
        return $this;
    }

    public function setFileName(string $fileName): static
    {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * Get the appropriate image driver based on available extensions.
     */
    protected function getDriver(): GdDriver|ImagickDriver
    {
        if (extension_loaded('imagick')) {
            return new ImagickDriver();
        }

        return new GdDriver();
    }

    /**
     * Save the processed image (crop or resize).
     *
     * @param string $type 'crop' or 'resize'
     * @return string The path to the saved image
     * @throws \Exception
     */
    public function save(string $type = 'crop'): string
    {
        if (empty($this->image)) {
            throw new \InvalidArgumentException('Image source is required');
        }

        if (empty($this->destinationPath) || empty($this->fileName)) {
            throw new \InvalidArgumentException('Destination path and filename are required');
        }

        $manager = new ImageManager($this->getDriver());

        // Read the image from file path or storage
        if (FilamentMedia::isUsingCloud()) {
            $imageContent = Storage::get($this->image);
            $image = $manager->read($imageContent);
        } else {
            $image = $manager->read($this->image);
        }

        // Apply transformation based on type
        if ($type === 'crop') {
            if ($this->width <= 0 || $this->height <= 0) {
                throw new \InvalidArgumentException('Width and height must be positive for crop operation');
            }
            $image->crop($this->width, $this->height, $this->x, $this->y);
        } else {
            // Resize - allow null dimensions for proportional scaling
            $image->resize($this->width ?: null, $this->height ?: null);
        }

        // Ensure destination directory exists
        $fullPath = $this->destinationPath . '/' . $this->fileName;

        if (FilamentMedia::isUsingCloud()) {
            // For cloud storage, encode and put
            $encoded = $image->encode();
            Storage::put($fullPath, (string) $encoded);
        } else {
            // For local storage, ensure directory exists and save directly
            File::ensureDirectoryExists($this->destinationPath);
            $image->save($fullPath);
        }

        return $fullPath;
    }

    /**
     * Generate a thumbnail with the specified size.
     *
     * @param string $sourcePath
     * @param string $size Format: "WIDTHxHEIGHT" (e.g., "150x150")
     * @param string $destinationPath
     * @return string The path to the saved thumbnail
     */
    public function generateThumbnail(string $sourcePath, string $size, string $destinationPath): string
    {
        [$width, $height] = explode('x', $size);

        return $this
            ->setImage($sourcePath)
            ->setSize((int) $width, (int) $height)
            ->setDestinationPath(dirname($destinationPath))
            ->setFileName(basename($destinationPath))
            ->save('resize');
    }
}