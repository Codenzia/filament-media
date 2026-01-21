<?php

namespace Codenzia\FilamentMedia\Http\Controllers;

use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Models\MediaSetting;
use Codenzia\FilamentMedia\Helpers\BaseHelper;
use Codenzia\FilamentMedia\Repositories\Interfaces\MediaFileInterface;
use Codenzia\FilamentMedia\Repositories\Interfaces\MediaFolderInterface;
use Codenzia\FilamentMedia\Services\UploadsManager;
use Codenzia\FilamentMedia\Http\Resources\FolderResource;
use Codenzia\FilamentMedia\Http\Resources\FileResource;
use Throwable;
use Illuminate\Support\Facades\Auth;
use Codenzia\FilamentMedia\Services\ThumbnailService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Codenzia\FilamentMedia\Supports\Zipper;
use Illuminate\Support\Facades\File;

class MediaController extends Controller
{
    public function __construct(
        protected MediaFileInterface $fileRepository,
        protected MediaFolderInterface $folderRepository,
        protected UploadsManager $uploadManager
    ) {
    }

    public function getMedia()
    {
        $this->pageTitle(trans('core/media::media.menu_name'));

        return view('core/media::index');
    }

    public function getPopup()
    {
        return view('core/media::popup')->render();
    }

    public function getList(Request $request)
    {
        $files = [];
        $folders = [];
        $breadcrumbs = [];

        $selectedFileId = $request->input('selected_file_id');

        if ($request->has('is_popup') && $selectedFileId) {
            $currentFile = MediaFile::query()->where(
                ['id' => $selectedFileId],
                ['folder_id']
            )->first();

            if ($currentFile) {
                $request->merge(['folder_id' => $currentFile->folder_id]);
            }
        }

        $paramsFolder = [];

        $paramsFile = [
            'order_by' => [
                'is_folder' => 'DESC',
            ],
            'paginate' => [
                'per_page' => $request->integer('posts_per_page', 30),
                'current_paged' => $request->integer('paged', 1),
            ],
            'selected_file_id' => $selectedFileId,
            'is_popup' => $request->input('is_popup'),
            'filter' => $request->input('filter'),
        ];

        $orderBy = $this->transformOrderBy($request->input('sort_by'));

        if (count($orderBy) > 1) {
            $paramsFile['order_by'][$orderBy[0]] = $orderBy[1];
        }

        $search = $request->input('search');

        if ($search) {
            $paramsFolder['condition'] = [
                ['media_folders.name', 'LIKE', '%' . $search . '%'],
            ];

            $paramsFile['condition'] = [
                ['media_files.name', 'LIKE', '%' . $search . '%'],
            ];
        }

        $folderId = $request->input('folder_id', 0);

        switch ($request->input('view_in')) {
            case 'all_media':
                $breadcrumbs = [
                    [
                        'id' => 0,
                        'name' => trans('core/media::media.all_media'),
                        'icon' => BaseHelper::renderIcon('heroicon-o-photo'),
                    ],
                ];

                $queried = $this->fileRepository->getFilesByFolderId($folderId, $paramsFile, true, $paramsFolder);

                $folders = FolderResource::collection($queried->where('is_folder', 1));

                $files = FileResource::collection($queried->where('is_folder', 0));

                break;

            case 'trash':
                $breadcrumbs = [
                    [
                        'id' => 0,
                        'name' => trans('core/media::media.trash'),
                        'icon' => BaseHelper::renderIcon('heroicon-m-trash'),
                    ],
                ];

                $queried = $this->fileRepository->getTrashed(
                    $folderId,
                    $paramsFile,
                    true,
                    $paramsFolder
                );

                $folders = FolderResource::collection($queried->where('is_folder', 1));

                $files = FileResource::collection($queried->where('is_folder', 0));

                break;

            case 'recent':
                $breadcrumbs = [
                    [
                        'id' => 0,
                        'name' => trans('core/media::media.recent'),
                        'icon' => BaseHelper::renderIcon('heroicon-m-clock'),
                    ],
                ];

                // Get recent items from the server instead of client-side localStorage
                $recentItems = MediaSetting::query()
                    ->where([
                        'key' => 'recent_items',
                        'user_id' => Auth::guard()->id(),
                    ])->first();

                // If we're in a specific folder, show all files in that folder
                if ($folderId > 0) {
                    // When in a specific folder, show all files in that folder like normal
                    $queried = $this->fileRepository->getFilesByFolderId(
                        $folderId,
                        $paramsFile,
                        true,
                        $paramsFolder
                    );

                    $folders = FolderResource::collection($queried->where('is_folder', 1));
                    $files = FileResource::collection($queried->where('is_folder', 0));
                } else {
                    // When in root recent view, show all recent items
                    if (! empty($recentItems)) {
                        $recentValue = $recentItems->value;

                        // Separate file IDs and folder IDs
                        $fileIds = [];
                        $folderIds = [];

                        foreach ($recentValue as $item) {
                            if (isset($item['is_folder']) && $item['is_folder']) {
                                $folderIds[] = $item['id'];
                            } else {
                                $fileIds[] = $item['id'];
                            }
                        }

                        // Get folders
                        if (count($folderIds) > 0) {
                            $paramsFolder = array_merge_recursive($paramsFolder, [
                                'condition' => [
                                    ['media_folders.id', 'IN', $folderIds],
                                ],
                            ]);
                        }

                        // Get files
                        if (count($fileIds) > 0) {
                            $paramsFile = array_merge_recursive($paramsFile, [
                                'condition' => [
                                    ['media_files.id', 'IN', $fileIds],
                                ],
                            ]);
                        }

                        if (count($fileIds) > 0 || count($folderIds) > 0) {
                            $queried = $this->fileRepository->getFilesByFolderId(
                                0,
                                $paramsFile,
                                true, // Include folders
                                $paramsFolder
                            );

                            $folders = FolderResource::collection($queried->where('is_folder', 1));
                            $files = FileResource::collection($queried->where('is_folder', 0));
                        }
                    }
                }

                break;

            case 'favorites':
                $breadcrumbs = [
                    [
                        'id' => 0,
                        'name' => trans('core/media::media.favorites'),
                        'icon' => BaseHelper::renderIcon('heroicon-m-star'),
                    ],
                ];

                $favoriteItems = MediaSetting::query()
                    ->where([
                        'key' => 'favorites',
                        'user_id' => Auth::guard()->id(),
                    ])->first();
                if (! empty($favoriteItems)) {
                    $favoriteCollection = collect($favoriteItems->value);

                    // Backward compatible: if is_folder is missing, treat as file
                    $fileIds = $favoriteCollection
                        ->filter(function ($item) {
                            return ($item['is_folder'] ?? false) === 'false' || ($item['is_folder'] ?? false) === false;
                        })
                        ->pluck('id')
                        ->filter()
                        ->all();

                    $folderIds = $favoriteCollection
                        ->filter(function ($item) {
                            return ($item['is_folder'] ?? false) === 'true' || ($item['is_folder'] ?? false) === true;
                        })
                        ->pluck('id')
                        ->filter()
                        ->all();

                    $paramsFile = array_merge_recursive($paramsFile, [
                        'condition' => [
                            ['media_files.id', 'IN', $fileIds],
                        ],
                    ]);

                    $paramsFolder = array_merge_recursive($paramsFolder, [
                        'condition' => [
                            ['media_folders.id', 'IN', $folderIds],
                        ],
                    ]);

                    // If we're in a specific folder, show all files in that folder
                    if ($folderId > 0) {
                        // When in a specific folder, show all files in that folder like normal
                        // Remove the favorites filter to show all files in the folder
                        $cleanParamsFile = $paramsFile;
                        if (isset($cleanParamsFile['condition'])) {
                            foreach ($cleanParamsFile['condition'] as $key => $condition) {
                                if (is_array($condition) && count($condition) > 2 && $condition[0] === 'media_files.id' && $condition[1] === 'IN') {
                                    unset($cleanParamsFile['condition'][$key]);
                                }
                            }
                        }

                        $cleanParamsFolder = $paramsFolder;
                        if (isset($cleanParamsFolder['condition'])) {
                            foreach ($cleanParamsFolder['condition'] as $key => $condition) {
                                if (is_array($condition) && count($condition) > 2 && $condition[0] === 'media_folders.id' && $condition[1] === 'IN') {
                                    unset($cleanParamsFolder['condition'][$key]);
                                }
                            }
                        }

                        $queried = $this->fileRepository->getFilesByFolderId(
                            $folderId,
                            $cleanParamsFile,
                            true,
                            $cleanParamsFolder
                        );
                    } else {
                        // When in root favorites view, show all favorited items regardless of folder
                        $paramsFile['is_favorite'] = true;

                        $queried = $this->fileRepository->getFilesByFolderId(
                            0,
                            $paramsFile,
                            true,
                            $paramsFolder
                        );
                    }

                    $folders = FolderResource::collection($queried->where('is_folder', 1));

                    $files = FileResource::collection($queried->where('is_folder', 0));
                }

                break;
        }

        $breadcrumbs = array_merge($breadcrumbs, $this->getBreadcrumbs($request));

        return FilamentMedia::responseSuccess([
            'files' => $files,
            'folders' => $folders,
            'breadcrumbs' => $breadcrumbs,
            'selected_file_id' => $selectedFileId,
        ]);
    }

    protected function transformOrderBy(?string $orderBy): array
    {
        $result = explode('-', $orderBy);
        if (! count($result) == 2) {
            return ['name', 'asc'];
        }

        return $result;
    }

    protected function getBreadcrumbs(Request $request): array
    {
        $folderId = $request->input('folder_id');

        if (! $folderId) {
            return [];
        }

        if ($request->input('view_in') == 'trash') {
            $folder = MediaFolder::query()->withTrashed()->find($folderId);
        } else {
            $folder = MediaFolder::query()->find($folderId);
        }

        if (empty($folder)) {
            return [];
        }

        $breadcrumbs = [
            [
                'name' => $folder->name,
                'id' => $folder->id,
            ],
        ];

        $child = $this->folderRepository->getBreadcrumbs($folder->parent_id);
        if (! empty($child)) {
            return array_merge($child, $breadcrumbs);
        }

        return $breadcrumbs;
    }

    public function postGlobalActions(Request $request, ThumbnailService $thumbnailService)
    {
        $response = FilamentMedia::responseError(trans('core/media::media.invalid_action'));

        $type = $request->input('action');

        switch ($type) {
            case 'trash':
                $error = false;

                $skipTrash = $request->input('skip_trash', false);

                foreach ($request->input('selected') as $item) {
                    $condition = [
                        'id' => $item['id'],
                    ];

                    if (! $item['is_folder']) {
                        try {
                            if ($skipTrash) {
                                $this->fileRepository->forceDelete($condition);
                            } else {
                                $this->fileRepository->deleteBy($condition);
                            }
                        } catch (Throwable $exception) {
                            BaseHelper::logError($exception);
                            $error = true;
                        }
                    } else {
                        if ($skipTrash) {
                            $this->folderRepository->forceDelete($condition);
                        } else {
                            $this->folderRepository->deleteFolder($item['id']);
                        }
                    }
                }

                if ($error) {
                    $response = FilamentMedia::responseError(trans('core/media::media.trash_error'));

                    break;
                }

                $response = FilamentMedia::responseSuccess([], trans('core/media::media.trash_success'));

                break;

            case 'restore':
                $error = false;
                foreach ($request->input('selected') as $item) {
                    $id = $item['id'];
                    if (! $item['is_folder']) {
                        try {
                            $this->fileRepository->restoreBy(['id' => $id]);
                        } catch (Throwable $exception) {
                            BaseHelper::logError($exception);
                            $error = true;
                        }
                    } else {
                        $this->folderRepository->restoreFolder($id);
                    }
                }

                if ($error) {
                    $response = FilamentMedia::responseError(trans('core/media::media.restore_error'));

                    break;
                }

                $response = FilamentMedia::responseSuccess([], trans('core/media::media.restore_success'));

                break;

            case 'move':
                $error = false;
                $newFolderId = $request->input('destination');

                foreach ($request->input('selected') as $item) {
                    if (! $item['is_folder']) {
                        try {
                            $file = $this->fileRepository->getFirstBy(['id' => $item['id']]);
                            if ($file) {
                                $this->moveFile($file, $newFolderId);
                            }
                        } catch (Throwable $exception) {
                            BaseHelper::logError($exception);
                            $error = true;
                        }
                    } else {
                        try {
                            $folder = $this->folderRepository->getFirstBy(['id' => $item['id']]);
                            if ($folder) {
                                $folder->parent_id = $newFolderId;
                                $folder->save();
                            }
                        } catch (Throwable $exception) {
                            BaseHelper::logError($exception);
                            $error = true;
                        }
                    }
                }

                if ($error) {
                    $response = FilamentMedia::responseError(trans('core/media::media.move_error'));

                    break;
                }

                $response = FilamentMedia::responseSuccess([], trans('core/media::media.move_success'));

                break;

            case 'make_copy':
                foreach ($request->input('selected', []) as $item) {
                    $id = $item['id'];
                    //if (! $item['is_folder']) {
                        /**
                         * @var MediaFile $file
                         */
                        $file = MediaFile::query()->find($id);

                        if (! $file) {
                            break;
                        }

                        $this->copyFile($file);
//                     } else {
//                         $oldFolder = MediaFolder::query()->find($id);
//
//                         if (! $oldFolder) {
//                             break;
//                         }
//
//                         $folderData = $oldFolder->replicate()->toArray();
//
//                         $folderData['slug'] = $this->folderRepository->createSlug(
//                             $oldFolder->name,
//                             $oldFolder->parent_id
//                         );
//                         $folderData['name'] = $oldFolder->name . '-(copy)';
//                         $folderData['user_id'] = Auth::guard()->id();
//                         $folder = $this->folderRepository->create($folderData);
//
//                         $files = $this->fileRepository->getFilesByFolderId($id, [], false);
//                         foreach ($files as $file) {
//                             $this->copyFile($file, $folder->id);
//                         }
//
//                         $children = $this->folderRepository->getAllChildFolders($id);
//                         foreach ($children as $parentId => $child) {
//                             if ($parentId != $oldFolder->getKey()) {
//                                 $folder = MediaFolder::query()->find($parentId);
//
//                                 if (! $folder) {
//                                     break;
//                                 }
//
//                                 $folderData = $folder->replicate()->toArray();
//
//                                 $folderData['slug'] = $this->folderRepository->createSlug(
//                                     $oldFolder->name,
//                                     $oldFolder->parent_id
//                                 );
//                                 $folderData['name'] = $oldFolder->name . '-(copy)';
//                                 $folderData['user_id'] = Auth::guard()->id();
//                                 $folderData['parent_id'] = $folder->id;
//                                 $folder = MediaFolder::query()->create($folderData);
//
//                                 $parentFiles = $this->fileRepository->getFilesByFolderId($parentId, [], false);
//                                 foreach ($parentFiles as $parentFile) {
//                                     $this->copyFile($parentFile, $folder->id);
//                                 }
//                             }
//
//                             foreach ($child as $sub) {
//                                 /**
//                                  * @var MediaFolder $sub
//                                  */
//                                 $subFiles = $this->fileRepository->getFilesByFolderId($sub->getKey(), [], false);
//
//                                 $subFolderData = $sub->replicate()->toArray();
//
//                                 $subFolderData['user_id'] = Auth::guard()->id();
//                                 $subFolderData['parent_id'] = $folder->id;
//
//                                 $sub = MediaFolder::query()->create($subFolderData);
//
//                                 foreach ($subFiles as $subFile) {
//                                     $this->copyFile($subFile, $sub->getKey());
//                                 }
//                             }
//                         }
//
//                         $allFiles = Storage::allFiles($this->folderRepository->getFullPath($oldFolder->getKey()));
//                         foreach ($allFiles as $file) {
//                             Storage::copy($file, str_replace($oldFolder->slug, $folder->slug, $file));
//                         }
//                     }
                }

                $response = FilamentMedia::responseSuccess([], trans('core/media::media.copy_success'));

                break;

            case 'delete':
                foreach ($request->input('selected') as $item) {
                    $id = $item['id'];
                    if (! $item['is_folder']) {
                        try {
                            $this->fileRepository->forceDelete(['id' => $id]);
                        } catch (Throwable $exception) {
                            BaseHelper::logError($exception);
                        }
                    } else {
                        $this->folderRepository->deleteFolder($id, true);
                    }
                }

                $response = FilamentMedia::responseSuccess([], trans('core/media::media.delete_success'));

                break;

            case 'favorite':
                $meta = MediaSetting::query()->firstOrCreate([
                    'key' => 'favorites',
                    'user_id' => Auth::guard()->id(),
                ]);

                if (! empty($meta->value)) {
                    $meta->value = array_merge($meta->value, $request->input('selected', []));
                } else {
                    $meta->value = $request->input('selected', []);
                }

                $meta->save();

                $response = FilamentMedia::responseSuccess([], trans('core/media::media.favorite_success'));

                break;

            case 'remove_favorite':
                $meta = MediaSetting::query()->firstOrCreate([
                    'key' => 'favorites',
                    'user_id' => Auth::guard()->id(),
                ]);

                if (! empty($meta)) {
                    $value = $meta->value;
                    if (! empty($value)) {
                        foreach ($value as $key => $item) {
                            foreach ($request->input('selected') as $selectedItem) {
                                if ($item['is_folder'] == $selectedItem['is_folder'] && $item['id'] == $selectedItem['id']) {
                                    unset($value[$key]);
                                }
                            }
                        }

                        $meta->value = $value;

                        $meta->save();
                    }
                }

                $response = FilamentMedia::responseSuccess([], trans('core/media::media.remove_favorite_success'));

                break;

            case 'add_recent':
                // Validate the input
                $itemId = $request->input('item.id');

                // If the item ID is not valid, return an error
                if (empty($itemId)) {
                    $response = FilamentMedia::responseError('Invalid item ID');

                    break;
                }

                $meta = MediaSetting::query()->firstOrCreate([
                    'key' => 'recent_items',
                    'user_id' => Auth::guard()->id(),
                ]);

                $value = $meta->value ?: [];

                // Add the new item to the recent items list
                // Only store essential information (id and is_folder)
                $recentItem = [
                    'id' => $itemId,
                    'is_folder' => (bool) $request->input('item.is_folder', false),
                ];

                // Check if the item already exists in the list
                foreach ($value as $key => $item) {
                    if ($item['id'] == $recentItem['id'] && isset($item['is_folder']) && $item['is_folder'] == $recentItem['is_folder']) {
                        // Remove it so we can add it to the beginning (most recent)
                        unset($value[$key]);

                        break;
                    }
                }

                // Add the item to the beginning of the array
                array_unshift($value, $recentItem);

                // Limit to 20 most recent items
                if (count($value) > 20) {
                    $value = array_slice($value, 0, 20);
                }

                $meta->value = $value;
                $meta->save();

                $response = FilamentMedia::responseSuccess([], trans('core/media::media.add_recent_success'));

                break;

            case 'crop':
                $validated = Validator::validate($request->input(), [
                    'imageId' => ['required', 'string', 'exists:media_files,id'],
                    'cropData' => ['required', 'json'],
                ]);

                $data = json_decode($validated['cropData'], true);

                $cropData = Validator::validate($data, [
                    'x' => ['required', 'numeric'],
                    'y' => ['required', 'numeric'],
                    'width' => ['required', 'numeric'],
                    'height' => ['required', 'numeric'],
                ]);

                /**
                 * @var MediaFile $file
                 */
                $file = MediaFile::query()->findOrFail($validated['imageId']);

                if (! $file->canGenerateThumbnails()) {
                    $response = FilamentMedia::responseError(trans('core/media::media.failed_to_crop_image'));

                    break;
                }

                $fileUrl = $file->url;
                $parsedUrl = parse_url($fileUrl);

                if (isset($parsedUrl['query'])) {
                    $fileUrl = str_replace('?' . $parsedUrl['query'], '', $fileUrl);
                }

                try {
                    $thumbnailService
                        ->setImage(FilamentMedia::getRealPath($fileUrl))
                        ->setSize((int) $cropData['width'], (int) $cropData['height'])
                        ->setCoordinates((int) $cropData['x'], (int) $cropData['y'])
                        ->setDestinationPath(File::dirname($fileUrl))
                        ->setFileName(File::name($fileUrl) . '.' . File::extension($fileUrl))
                        ->save('crop');
                } catch (UnableToWriteFile $exception) {
                    $message = $exception->getMessage();

                    if (! FilamentMedia::isUsingCloud()) {
                        $message = trans('core/media::media.unable_to_write', ['folder' => FilamentMedia::getUploadPath()]);
                    }

                    return FilamentMedia::responseError($message);
                } catch (Throwable $exception) {
                    return FilamentMedia::responseError($exception->getMessage());
                }

                $file->url = $fileUrl . '?v=' . time();
                $file->save();

                FilamentMedia::generateThumbnails($file);

                $response = FilamentMedia::responseSuccess([], trans('core/media::media.crop_success'));

                break;

            case 'rename':
                Validator::validate($request->input(), [
                    'selected' => ['required', 'array'],
                    'selected.*.id' => ['required', 'string'],
                    'selected.*.name' => ['required', 'string', 'max:120'],
                    'selected.*.is_folder' => ['required', 'boolean'],
                ]);

                foreach ($request->input('selected') as $item) {
                    $id = $item['id'];

                    if (! $item['is_folder']) {
                        /**
                         * @var MediaFile $file
                         */
                        $file = MediaFile::query()->find($id);

                        if (! empty($file)) {
                            FilamentMedia::renameFile(
                                file: $file,
                                newName: $item['name'],
                                renameOnDisk: Arr::get($item, 'rename_physical_file', false)
                            );
                        }
                    } else {
                        $name = $item['name'];
                        /**
                         * @var MediaFolder $folder
                         */
                        $folder = MediaFolder::query()->find($id);

                        if (! empty($folder)) {
                            FilamentMedia::renameFolder(
                                folder: $folder,
                                newName: $name,
                                renameOnDisk: Arr::get($item, 'rename_physical_file', false)
                            );
                        }
                    }
                }

                $response = FilamentMedia::responseSuccess([], trans('core/media::media.rename_success'));

                break;

            case 'alt_text':
                Validator::validate($request->input(), [
                    'selected' => ['required', 'array'],
                    'selected.*.id' => ['required', 'exists:media_files,id'],
                    'selected.*.alt' => ['nullable', 'string', 'max:220'],
                ]);

                foreach ($request->input('selected') as $item) {
                    if (! $item['id']) {
                        continue;
                    }

                    MediaFile::query()->where('id', $item['id'])->update(['alt' => $item['alt']]);
                }

                $response = FilamentMedia::responseSuccess([], trans('core/media::media.update_alt_text_success'));

                break;
            case 'empty_trash':
                $this->fileRepository->emptyTrash();
                $this->folderRepository->emptyTrash();

                $response = FilamentMedia::responseSuccess([], trans('core/media::media.empty_trash_success'));

                break;

            case 'properties':
                Validator::validate($request->input(), [
                    'color' => ['required', 'string', Rule::in(FilamentMedia::getFolderColors())],
                    'selected' => ['required', 'array'],
                    'selected.*' => ['required', 'string', 'exists:media_folders,id'],
                ]);

                MediaFolder::query()->whereIn('id', $request->input('selected'))->update([
                    'color' => $request->input('color'),
                ]);

                $response = FilamentMedia::responseSuccess([], trans('core/media::media.update_properties_success'));

                break;
        }

        return $response;
    }

    protected function copyFile(MediaFile $file, int|string|null $newFolderId = null)
    {
        $file = $file->replicate();
        $file->user_id = Auth::guard()->id();

        if ($newFolderId == null) {
            $file->name = $file->name . '-(copy)';

            $path = '';

            $folderPath = File::dirname($file->url);
            if ($folderPath) {
                $path = $folderPath . '/' . $path;
            }

            $path = $path . File::name($file->url) . '-(copy)' . '.' . File::extension($file->url);

            $filePath = FilamentMedia::getRealPath($file->url);
            if (Storage::exists($filePath)) {
                $content = File::get($filePath);

                $this->uploadManager->saveFile($path, $content);
                $file->url = $path;

                FilamentMedia::generateThumbnails($file);
            }
        } else {
            $file->url = str_replace(
                FilamentMedia::getRealPath(File::dirname($file->url)),
                FilamentMedia::getRealPath($this->folderRepository->getFullPath($newFolderId)),
                $file->url
            );

            $file->folder_id = $newFolderId;
        }

        unset($file->is_folder);
        unset($file->slug);
        unset($file->parent_id);
        unset($file->color);

        $file->save();

        return $file;
    }

    protected function moveFile(MediaFile $file, int|string|null $newFolderId = null)
    {
        if ($newFolderId === null) {
            return $file;
        }

        $oldPath = FilamentMedia::getRealPath($file->url);
        $newFolderPath = FilamentMedia::getRealPath($this->folderRepository->getFullPath($newFolderId));
        $newPath = $newFolderPath . '/' . File::basename($file->url);

        if (Storage::exists($oldPath)) {
            Storage::move($oldPath, $newPath);
            $file->url = str_replace(
                FilamentMedia::getRealPath(File::dirname($file->url)),
                FilamentMedia::getRealPath($this->folderRepository->getFullPath($newFolderId)),
                $file->url
            );
            $file->folder_id = $newFolderId;
            $file->save();
        }

        return $file;
    }

    public function download(Request $request)
    {
        $items = $request->input('selected', []);
        if (count($items) == 1 && $items[0]['is_folder'] == "false") {
            $file = MediaFile::query()->withTrashed()->find($items[0]['id']);
            if (! empty($file) && $file->type != 'video') {
                return FilamentMedia::responseDownloadFile($file->url);
            }
        } else {
            $fileName = Storage::disk('local')->path('download-' . Carbon::now()->format('Y-m-d-H-i-s') . '.zip');
            $zip = new Zipper();
            $zip->make($fileName);
            foreach ($items as $item) {
                $id = $item['id'];
                if (! $item['is_folder']) {
                    $file = MediaFile::query()->withTrashed()->find($id);
                    if (! empty($file) && $file->type != 'video') {
                        $filePath = FilamentMedia::getRealPath($file->url);
                        if (! FilamentMedia::isUsingCloud()) {
                            if (File::exists($filePath)) {
                                $zip->add($filePath);
                            }
                        } else {
                            $zip->addString(
                                File::basename($file),
                                Http::withoutVerifying()->get($filePath)->body()
                            );
                        }
                    }
                } else {
                    $folder = MediaFolder::query()->withTrashed()->find($id);
                    if (! empty($folder)) {
                        if (! FilamentMedia::isUsingCloud()) {
                            $folderPath = FilamentMedia::getRealPath($this->folderRepository->getFullPath($folder->id));
                            if (File::isDirectory($folderPath)) {
                                $zip->add($folderPath);
                            }
                        } else {
                            $allFiles = Storage::allFiles($this->folderRepository->getFullPath($folder->id));
                            foreach ($allFiles as $file) {
                                $zip->addString(
                                    File::basename($file),
                                    Http::withoutVerifying()->get(FilamentMedia::getRealPath($file))->body()
                                );
                            }
                        }
                    }
                }
            }

            if (File::exists($fileName)) {
                return response()
                    ->download($fileName, File::name($fileName))
                    ->deleteFileAfterSend();
            }

            return FilamentMedia::responseError(trans('core/media::media.download_file_error'));
        }

        return FilamentMedia::responseError(trans('core/media::media.can_not_download_file'));
    }
}
