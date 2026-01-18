<?php

namespace Codenzia\FilamentMedia\Services;

use Codenzia\FilamentMedia\Facades\FilamentMedia as RvMedia;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Illuminate\Support\Facades\File;

class ThumbnailService
{
    protected $image;
    protected $width;
    protected $height;
    protected $x = 0;
    protected $y = 0;
    protected $destinationPath;
    protected $fileName;

    public function setImage($image)
    {
        $this->image = $image;
        return $this;
    }

    public function setSize($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
        return $this;
    }

    public function setCoordinates($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
        return $this;
    }

    public function setDestinationPath($path)
    {
        $this->destinationPath = $path;
        return $this;
    }

    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function save($type = 'crop')
    {
        $manager = new ImageManager(new GdDriver());
        
        $image = $manager->read($this->image);

        if ($type === 'crop') {
            $image->crop($this->width, $this->height, $this->x, $this->y);
        } else {
            $image->resize($this->width, $this->height);
        }

        $path = $this->destinationPath . '/' . $this->fileName;
        
        File::ensureDirectoryExists($this->destinationPath);
        
        $image->save($path);

        return $path;
    }
}
