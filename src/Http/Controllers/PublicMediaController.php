<?php

namespace Codenzia\FilamentMedia\Http\Controllers;

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Services\MediaUrlService;
use Codenzia\FilamentMedia\Services\StorageDriverService;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PublicMediaController extends Controller
{
    public function show(string $hash, string $id): Response
    {
        $expectedHash = sha1($id);

        if ($hash !== $expectedHash) {
            abort(404);
        }

        $file = MediaFile::withoutGlobalScopes()->find($id);

        if (! $file) {
            abort(404);
        }

        if ($file->visibility !== 'public') {
            abort(403, 'This file is not publicly accessible.');
        }

        $urlService = app(MediaUrlService::class);
        $storageDriver = app(StorageDriverService::class);

        $path = $urlService->getRealPath($file->url);

        if ($storageDriver->isUsingCloud()) {
            $url = Storage::url($file->url);

            return redirect($url);
        }

        if (! file_exists($path)) {
            abort(404);
        }

        $mimeType = $file->mime_type ?? mime_content_type($path);
        $fileName = $file->name;

        $extension = pathinfo($file->url, PATHINFO_EXTENSION);
        if ($extension && ! str_ends_with($fileName, '.' . $extension)) {
            $fileName .= '.' . $extension;
        }

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
