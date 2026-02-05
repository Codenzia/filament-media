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

    /**
     * Get breadcrumbs for a folder.
     * Optimized to fetch all ancestors in a single query using CTE or iterative approach.
     */
    public function getBreadcrumbs(int|string|null $parentId, array $breadcrumbs = [])
    {
        if (!$parentId || $parentId == 0) {
            return $breadcrumbs;
        }

        // Collect all parent IDs first to minimize queries
        $ancestors = [];
        $currentId = $parentId;
        $maxDepth = 50; // Prevent infinite loops
        $depth = 0;

        // First, get all folders that might be ancestors (single query)
        $allFolders = $this->model
            ->select(['id', 'name', 'parent_id'])
            ->get()
            ->keyBy('id');

        // Build breadcrumbs by traversing the cached data
        while ($currentId && $currentId != 0 && $depth < $maxDepth) {
            $folder = $allFolders->get($currentId);
            if (!$folder) {
                break;
            }
            $ancestors[] = [
                'name' => $folder->name,
                'id' => $folder->id,
            ];
            $currentId = $folder->parent_id;
            $depth++;
        }

        // Return ancestors in correct order (root first)
        return array_merge(array_reverse($ancestors), $breadcrumbs);
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

    /**
     * Get all child folders recursively.
     * Optimized to fetch all folders in a single query and filter in memory.
     */
    public function getAllChildFolders(int|string|null $parentId, array $child = [])
    {
        if (!$parentId) {
            return $child;
        }

        // Fetch all folders in a single query
        $allFolders = $this->model
            ->select(['id', 'name', 'parent_id', 'slug'])
            ->get();

        // Build a lookup by parent_id for efficient traversal
        $foldersByParent = $allFolders->groupBy('parent_id');

        // Recursively collect children using in-memory traversal
        $this->collectChildFolders($parentId, $foldersByParent, $allFolders->keyBy('id'), $child);

        return $child;
    }

    /**
     * Helper method to recursively collect child folders from cached data.
     */
    protected function collectChildFolders(
        int|string $parentId,
        $foldersByParent,
        $foldersById,
        array &$child
    ): void {
        $children = $foldersByParent->get($parentId, collect());

        foreach ($children as $folder) {
            $child[$folder->id] = $foldersById->get($folder->id);
            $this->collectChildFolders($folder->id, $foldersByParent, $foldersById, $child);
        }
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
