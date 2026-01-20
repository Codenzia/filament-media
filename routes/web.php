<?php

use Illuminate\Support\Facades\Route;
use Codenzia\FilamentMedia\Http\Controllers\MediaController;

Route::get('media/files/{hash}/{id}', [
    'as' => 'media.indirect.url',
    'uses' => 'PublicMediaController@show',
    'middleware' => 'throttle',
]);

Route::group(['prefix' => 'media', 'as' => 'media.', 'middleware' => ['web', 'auth']], function () {
    Route::get('/', [MediaController::class, 'index'])->name('index');
    Route::get('list', [MediaController::class, 'getList'])->name('list');
    Route::post('folders/create', [MediaController::class, 'postCreateFolder'])->name('folders.create');
    Route::get('popup', [MediaController::class, 'getPopup'])->name('popup');
    Route::post('download', [MediaController::class, 'download'])->name('download');
    Route::post('files/upload', [MediaController::class, 'postUploadFile'])->name('files.upload');
    Route::get('breadcrumbs', [MediaController::class, 'getBreadcrumbs'])->name('breadcrumbs');
    Route::post('global-actions', [MediaController::class, 'postGlobalActions'])->name('global_actions');
    Route::post('files/upload-from-editor', [MediaController::class, 'postUploadFromEditor'])->name('files.upload.from.editor');
    Route::post('download-url', [MediaController::class, 'postDownloadUrl'])->name('download_url');
});
