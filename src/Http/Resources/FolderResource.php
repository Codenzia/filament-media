<?php

namespace Codenzia\FilamentMedia\Http\Resources;

use Codenzia\FilamentMedia\Helpers\BaseHelper;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource that transforms a MediaFolder model into its JSON representation
 * with optional nested file resources.
 *
 * @mixin MediaFolder
 */
class FolderResource extends JsonResource
{
    protected Collection $files;

    public function toArray($request): array
    {
        $totalSize = $this->files_sum_size ?? null;

        return [
            'id' => $this->id,
            'is_folder' => true,
            'name' => $this->name,
            'color' => $this->color,
            'size' => $totalSize ? BaseHelper::humanFilesize((int) $totalSize) : null,
            'created_at' => BaseHelper::formatDate($this->created_at, 'Y-m-d H:i:s'),
            'updated_at' => BaseHelper::formatDate($this->updated_at, 'Y-m-d H:i:s'),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')->toArray(), []),
            ...isset($this->files) ? [
                'files' => FileResource::collection($this->files),
            ] : [],
        ];
    }

    public function withFiles(Collection $files): self
    {
        $this->files = $files;

        return $this;
    }
}
