<?php

namespace Codenzia\FilamentMedia\Http\Resources;

use Codenzia\FilamentMedia\Helpers\BaseHelper;
use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Codenzia\FilamentMedia\Models\MediaFile;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin MediaFile
 */
class FileResource extends JsonResource
{
    public function toArray($request): array
    {
        // Check if file exists on disk
        $fileExists = $this->checkFileExists();

        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'basename' => File::basename($this->url),
            'url' => $this->url,
            'full_url' => $this->visibility === 'public' ? Storage::disk('public')->url($this->url) : null,
            'type' => $this->type,
            'icon' => $this->icon,
            'thumb' => $fileExists && $this->canGenerateThumbnails() ? Storage::disk('public')->url($this->url, 'thumb') : null,
            'size' => $this->human_size,
            'mime_type' => $this->mime_type,
            'created_at' => BaseHelper::formatDate($this->created_at, 'Y-m-d H:i:s'),
            'updated_at' => BaseHelper::formatDate($this->updated_at, 'Y-m-d H:i:s'),
            'options' => $this->options,
            'folder_id' => $this->folder_id,
            'preview_url' => $this->preview_url,
            'preview_type' => $this->preview_type,
            'indirect_url' => $this->indirect_url,
            'alt' => $this->alt,
            'file_exists' => $fileExists,
        ];
    }

    /**
     * Check if the file exists on disk.
     */
    protected function checkFileExists(): bool
    {
        if (empty($this->url)) {
            return false;
        }

        try {
            // For cloud storage, we trust it exists (checking would be slow)
            if (FilamentMedia::isUsingCloud()) {
                return true;
            }

            // For local storage, check if file exists
            return Storage::disk(FilamentMedia::getConfig('driver', 'public'))->exists($this->url);
        } catch (\Throwable $e) {
            // If we can't check, assume it doesn't exist
            return false;
        }
    }
}
