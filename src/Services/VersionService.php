<?php

namespace Codenzia\FilamentMedia\Services;

use Codenzia\FilamentMedia\Events\MediaFileVersionCreated;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFileVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class VersionService
{
    public function __construct(
        protected UploadService $uploadService,
        protected MediaUrlService $urlService
    ) {}

    /**
     * Create a new version by snapshotting the current file and replacing it with the new upload.
     */
    public function createVersion(MediaFile $file, UploadedFile $newFile, ?string $changelog = null): MediaFileVersion
    {
        // Snapshot current file as a version
        $latestVersion = $file->latestVersion;
        $nextVersionNumber = $latestVersion ? $latestVersion->version_number + 1 : 1;

        $version = MediaFileVersion::create([
            'media_file_id' => $file->id,
            'version_number' => $nextVersionNumber,
            'url' => $file->url,
            'size' => $file->size,
            'mime_type' => $file->mime_type,
            'user_id' => Auth::guard()->check() ? Auth::guard()->id() : null,
            'changelog' => $changelog,
        ]);

        // Upload and replace with the new file
        $result = $this->uploadService->handleUpload($newFile, $file->folder_id);

        if (! $result['error']) {
            $newFileData = $result['data'];

            $file->update([
                'url' => $newFileData->url ?? $file->url,
                'size' => $newFileData->size ?? $file->size,
                'mime_type' => $newFileData->mime_type ?? $file->mime_type,
            ]);
        }

        MediaFileVersionCreated::dispatch($file, $version);

        return $version;
    }

    public function getVersions(MediaFile $file): Collection
    {
        return $file->versions()->with('user')->get();
    }

    public function revertToVersion(MediaFile $file, int $versionId): MediaFile
    {
        $version = MediaFileVersion::where('media_file_id', $file->id)
            ->findOrFail($versionId);

        // Snapshot current as new version before reverting
        $latestVersion = $file->latestVersion;
        $nextVersionNumber = $latestVersion ? $latestVersion->version_number + 1 : 1;

        MediaFileVersion::create([
            'media_file_id' => $file->id,
            'version_number' => $nextVersionNumber,
            'url' => $file->url,
            'size' => $file->size,
            'mime_type' => $file->mime_type,
            'user_id' => Auth::guard()->check() ? Auth::guard()->id() : null,
            'changelog' => "Reverted to version {$version->version_number}",
        ]);

        $file->update([
            'url' => $version->url,
            'size' => $version->size,
            'mime_type' => $version->mime_type,
        ]);

        return $file->fresh();
    }

    public function deleteVersion(int $versionId): bool
    {
        return MediaFileVersion::findOrFail($versionId)->delete();
    }

    public function getVersionDiff(int $versionId1, int $versionId2): array
    {
        $v1 = MediaFileVersion::findOrFail($versionId1);
        $v2 = MediaFileVersion::findOrFail($versionId2);

        return [
            'size_diff' => $v2->size - $v1->size,
            'date_diff' => $v1->created_at->diffForHumans($v2->created_at),
            'mime_changed' => $v1->mime_type !== $v2->mime_type,
            'version_1' => $v1->toArray(),
            'version_2' => $v2->toArray(),
        ];
    }

    public function pruneOldVersions(MediaFile $file, int $keepCount = 10): int
    {
        $versions = $file->versions()->orderByDesc('version_number')->get();

        if ($versions->count() <= $keepCount) {
            return 0;
        }

        $toDelete = $versions->slice($keepCount);
        $count = $toDelete->count();

        MediaFileVersion::whereIn('id', $toDelete->pluck('id'))->delete();

        return $count;
    }
}
