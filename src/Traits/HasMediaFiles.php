<?php

namespace Codenzia\FilamentMedia\Traits;

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMediaFiles
{
    /**
     * Get all media files associated with this model.
     */
    public function files(): MorphMany
    {
        return $this->morphMany(MediaFile::class, 'fileable');
    }

    /**
     * Get all media folders associated with this model.
     */
    public function folders(): MorphMany
    {
        return $this->morphMany(MediaFolder::class, 'fileable');
    }

    /**
     * Get all image files.
     */
    public function images(): MorphMany
    {
        return $this->files()->where('mime_type', 'like', 'image/%');
    }

    /**
     * Get all video files.
     */
    public function videos(): MorphMany
    {
        return $this->files()->where('mime_type', 'like', 'video/%');
    }

    /**
     * Get all document files (PDFs, Office documents, text files).
     */
    public function documents(): MorphMany
    {
        return $this->files()->where(function ($query) {
            $query->where('mime_type', 'like', 'application/pdf')
                  ->orWhere('mime_type', 'like', 'application/msword')
                  ->orWhere('mime_type', 'like', 'application/vnd.%')
                  ->orWhere('mime_type', 'like', 'text/%');
        });
    }

    /**
     * Get all audio files.
     */
    public function audio(): MorphMany
    {
        return $this->files()->where('mime_type', 'like', 'audio/%');
    }

    /**
     * Attach a media file to this model.
     */
    public function attachMediaFile(MediaFile $file): MediaFile
    {
        $file->fileable()->associate($this);
        $file->save();

        return $file;
    }

    /**
     * Attach multiple media files to this model.
     *
     * @param array<MediaFile>|Collection $files
     */
    public function attachMediaFiles($files): void
    {
        foreach ($files as $file) {
            $this->attachMediaFile($file);
        }
    }

    /**
     * Detach a media file from this model.
     */
    public function detachMediaFile(MediaFile $file): MediaFile
    {
        $file->fileable()->dissociate();
        $file->save();

        return $file;
    }

    /**
     * Detach all media files from this model.
     */
    public function detachAllMediaFiles(): void
    {
        $this->files()->update([
            'fileable_type' => null,
            'fileable_id' => null,
        ]);
    }

    /**
     * Get the first media file.
     */
    public function getFirstMediaFile(): ?MediaFile
    {
        return $this->files()->first();
    }

    /**
     * Get the first image.
     */
    public function getFirstImage(): ?MediaFile
    {
        return $this->images()->first();
    }

    /**
     * Get the URL of the first media file.
     */
    public function getFirstMediaUrl(): ?string
    {
        $file = $this->getFirstMediaFile();

        return $file?->url;
    }

    /**
     * Get the URL of the first image.
     */
    public function getFirstImageUrl(): ?string
    {
        $file = $this->getFirstImage();

        return $file?->url;
    }

    /**
     * Check if the model has any media files.
     */
    public function hasMediaFiles(): bool
    {
        return $this->files()->exists();
    }

    /**
     * Check if the model has any images.
     */
    public function hasImages(): bool
    {
        return $this->images()->exists();
    }

    /**
     * Sync media files - detach all current and attach new ones.
     *
     * @param array<MediaFile>|Collection $files
     */
    public function syncMediaFiles($files): void
    {
        $this->detachAllMediaFiles();
        $this->attachMediaFiles($files);
    }
}
