<?php

namespace Codenzia\FilamentMedia\Http\Resources;

use Codenzia\FilamentMedia\Helpers\BaseHelper;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Services\MediaUrlService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\File;

/**
 * API resource that transforms a MediaFile model into its JSON representation
 * including URLs, thumbnails, metadata, and linked model information.
 *
 * @mixin MediaFile
 */
class FileResource extends JsonResource
{
    public function toArray($request): array
    {
        $urlService = app(MediaUrlService::class);
        $fileExists = $urlService->fileExists($this->url);
        $linkedModel = $this->getLinkedModelInfo();
        $displayUrl = $urlService->visibilityAwareUrl($this->resource);

        return [
            'id' => $this->getKey(),
            'is_folder' => false,
            'name' => $this->name,
            'basename' => File::basename($this->url),
            'url' => $this->url,
            'full_url' => $displayUrl,
            'type' => $this->type,
            'icon' => $this->icon,
            'thumb' => $this->resolveThumbUrl($fileExists, $urlService, $displayUrl),
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
            'visibility' => $this->visibility,
            'linked_model_url' => $linkedModel['url'] ?? null,
            'linked_model_label' => $linkedModel['label'] ?? null,
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')->toArray(), []),
        ];
    }

    protected function resolveThumbUrl(bool $fileExists, MediaUrlService $urlService, string $displayUrl): ?string
    {
        if (! $fileExists) {
            return null;
        }

        if ($this->visibility !== 'private' && $this->canGenerateThumbnails()) {
            return $urlService->url($this->url);
        }

        if ($this->visibility === 'private' && str_starts_with($this->mime_type ?? '', 'image/')) {
            return $displayUrl;
        }

        return null;
    }
}
