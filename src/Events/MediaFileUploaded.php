<?php

namespace Codenzia\FilamentMedia\Events;

use Codenzia\FilamentMedia\Models\MediaFile;
use Illuminate\Foundation\Events\Dispatchable;

class MediaFileUploaded
{
    use Dispatchable;

    public function __construct(public MediaFile $file)
    {
    }
}
