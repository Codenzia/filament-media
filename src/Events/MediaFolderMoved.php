<?php

namespace Codenzia\FilamentMedia\Events;

use Codenzia\FilamentMedia\Models\MediaFolder;
use Illuminate\Foundation\Events\Dispatchable;

class MediaFolderMoved
{
    use Dispatchable;

    public function __construct(public MediaFolder $folder, public int|string|null $oldParentId, public int|string|null $newParentId)
    {
    }
}
