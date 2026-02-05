<?php

use Illuminate\Support\Facades\Route;
use Codenzia\FilamentMedia\Http\Controllers\MediaController;
use Codenzia\FilamentMedia\Http\Controllers\MediaFileController;
use Codenzia\FilamentMedia\Http\Controllers\PublicMediaController;

// Public route for indirect media file access
Route::get('media/files/{hash}/{id}', [PublicMediaController::class, 'show'])
    ->name('media.indirect.url')
    ->middleware(['web', 'throttle:60,1']);

// Protected media management routes
Route::group(['prefix' => 'media', 'as' => 'media.', 'middleware' => ['web', 'auth']], function () {
    Route::get('/', [MediaController::class, 'getMedia'])->name('index');
    Route::get('list', [MediaController::class, 'getList'])->name('list');
    Route::post('folders/create', [MediaController::class, 'postCreateFolder'])->name('folders.create');
    Route::get('popup', [MediaController::class, 'getPopup'])->name('popup');
    Route::post('download', [MediaController::class, 'download'])->name('download');
    Route::post('files/upload', [MediaFileController::class, 'postUpload'])->name('files.upload');
    Route::get('breadcrumbs', [MediaController::class, 'getBreadcrumbs'])->name('breadcrumbs');
    Route::post('global-actions', [MediaController::class, 'postGlobalActions'])->name('global_actions');
    Route::post('files/upload-from-editor', [MediaFileController::class, 'postUploadFromEditor'])->name('files.upload.from.editor');
    Route::post('download-url', [MediaFileController::class, 'postDownloadUrl'])->name('download_url');
});
