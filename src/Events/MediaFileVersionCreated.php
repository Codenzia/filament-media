<?php

namespace Codenzia\FilamentMedia\Events;

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFileVersion;
use Illuminate\Foundation\Events\Dispatchable;

class MediaFileVersionCreated
{
    use Dispatchable;

    public function __construct(public MediaFile $file, public MediaFileVersion $version)
    {
    }
}
