<?php

namespace Codenzia\FilamentMedia\Events;

use Codenzia\FilamentMedia\Models\MediaFolder;
use Illuminate\Foundation\Events\Dispatchable;

class MediaFolderRenamed
{
    use Dispatchable;

    public function __construct(public MediaFolder $folder)
    {
    }
}
