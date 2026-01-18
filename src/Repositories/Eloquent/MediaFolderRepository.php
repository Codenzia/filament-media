<?php

namespace Codenzia\FilamentMedia\Repositories\Eloquent;

use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Repositories\Interfaces\MediaFolderInterface;
use Illuminate\Support\Facades\Auth;

class MediaFolderRepository extends BaseRepository implements MediaFolderInterface
{
    public function __construct(MediaFolder $model)
    {
        parent::__construct($model);
    }

    public function getFolderByParentId(int|string|null $folderId, array $params = [], bool $withTrash = false)
    {
        $params = array_merge([
            'condition' => [],
            'order_by' => [
                'name' => 'ASC',
            ],
            'select' => ['*'],
            'with' => [],
        ], $params);

        $params['condition']['parent_id'] = $folderId;

        if ($withTrash) {
            // Implement logic to include trash
        }

        return $this->advancedGet($params);
    }

    public function createSlug(string $name, int|string|null $parentId): string
    {
        return MediaFolder::createSlug($name, $parentId);
    }

    public function createName(string $name, int|string|null $parentId): string
    {
        return MediaFolder::createName($name, $parentId);
    }

    public function getBreadcrumbs(int|string|null $parentId, array $breadcrumbs = [])
    {
        if ($parentId == 0) {
            return $breadcrumbs;
        }

        $folder = $this->findById($parentId);

        if ($folder) {
            $breadcrumbs[] = [
                'name' => $folder->name,
                'id' => $folder->id,
            ];
            return $this->getBreadcrumbs($folder->parent_id, $breadcrumbs);
        }

        return $breadcrumbs;
    }

    public function getTrashed(int|string|null $parentId, array $params = [])
    {
        $params = array_merge([
            'condition' => [],
            'order_by' => [
                'name' => 'ASC',
            ],
            'select' => ['*'],
            'with' => [],
        ], $params);

        $params['condition']['parent_id'] = $parentId;
        
        $query = $this->model->onlyTrashed();
        return $this->applyBeforeExecuteQuery($query->where($params['condition'])->get());
    }

    public function deleteFolder(int|string|null $folderId, bool $force = false)
    {
        $folder = $this->findById($folderId);
        if ($folder) {
             if ($force) {
                 return $folder->forceDelete();
             }
             return $folder->delete();
        }
        return false;
    }

    public function getAllChildFolders(int|string|null $parentId, array $child = [])
    {
        $folders = $this->model->where('parent_id', $parentId)->get();
        foreach ($folders as $folder) {
            $child[$folder->id] = $folder;
            $child = $this->getAllChildFolders($folder->id, $child);
        }
        return $child;
    }

    public function getFullPath(int|string|null $folderId, ?string $path = ''): ?string
    {
        return MediaFolder::getFullPath($folderId, $path);
    }

    public function restoreFolder(int|string|null $folderId)
    {
        $folder = $this->model->withTrashed()->find($folderId);
        if ($folder) {
            return $folder->restore();
        }
        return false;
    }

    public function emptyTrash(): bool
    {
        return $this->model->onlyTrashed()->forceDelete();
    }
}
