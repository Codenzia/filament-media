<?php

namespace Codenzia\FilamentMedia\Events;

use Codenzia\FilamentMedia\Models\MediaFile;
use Illuminate\Foundation\Events\Dispatchable;

class MediaFileCopied
{
    use Dispatchable;

    public function __construct(public MediaFile $newFile, public MediaFile $originalFile)
    {
    }
}
