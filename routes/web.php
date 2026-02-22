<?php

use Illuminate\Support\Facades\Route;
use Codenzia\FilamentMedia\Http\Controllers\MediaFileController;
use Codenzia\FilamentMedia\Http\Controllers\PrivateMediaController;
use Codenzia\FilamentMedia\Http\Controllers\PublicMediaController;

// Public route for indirect media file access
Route::get('media/files/{hash}/{id}', [PublicMediaController::class, 'show'])
    ->name('media.indirect.url')
    ->middleware(['web', 'throttle:60,1']);

// Authenticated routes for private media file access
Route::get('media/private/{hash}/{id}', [PrivateMediaController::class, 'show'])
    ->name('media.private.url')
    ->middleware(['web', 'auth', 'throttle:60,1']);

Route::get('media/private/{hash}/{id}/thumb/{size}', [PrivateMediaController::class, 'showThumbnail'])
    ->name('media.private.thumb')
    ->middleware(['web', 'auth', 'throttle:120,1']);

// Protected media file routes
Route::group(['prefix' => 'media', 'as' => 'media.', 'middleware' => ['web', 'auth']], function () {
    Route::post('files/upload', [MediaFileController::class, 'postUpload'])->name('files.upload');
    Route::post('files/upload-from-editor', [MediaFileController::class, 'postUploadFromEditor'])->name('files.upload.from.editor');
    Route::post('download-url', [MediaFileController::class, 'postDownloadUrl'])->name('download_url');
});
