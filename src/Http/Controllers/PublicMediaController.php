<?php

namespace Codenzia\FilamentMedia\Http\Controllers;

use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Codenzia\FilamentMedia\Models\MediaFile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicMediaController extends Controller
{
    /**
     * Show a media file via indirect URL.
     *
     * @param string $hash
     * @param string $id
     * @return Response|StreamedResponse
     */
    public function show(string $hash, string $id)
    {
        // Validate the hash matches the ID
        $expectedHash = sha1($id);

        if ($hash !== $expectedHash) {
            abort(404);
        }

        // Find the media file
        $file = MediaFile::withoutGlobalScopes()->find($id);

        if (!$file) {
            abort(404);
        }

        // Check if file is public
        if ($file->visibility !== 'public') {
            abort(403, 'This file is not publicly accessible.');
        }

        // Get the file path
        $path = FilamentMedia::getRealPath($file->url);

        // Check if using cloud storage
        if (FilamentMedia::isUsingCloud()) {
            // For cloud storage, redirect to the URL or stream
            $url = Storage::url($file->url);
            return redirect($url);
        }

        // For local storage, stream the file
        if (!file_exists($path)) {
            abort(404);
        }

        $mimeType = $file->mime_type ?? mime_content_type($path);
        $fileName = $file->name;

        // Add extension if not present
        $extension = pathinfo($file->url, PATHINFO_EXTENSION);
        if ($extension && !str_ends_with($fileName, '.' . $extension)) {
            $fileName .= '.' . $extension;
        }

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
