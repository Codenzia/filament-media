<?php

namespace Codenzia\FilamentMedia\Http\Controllers;

use Codenzia\FilamentMedia\Exceptions\MediaUploadException;
use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Codenzia\FilamentMedia\Services\MediaUrlService;
use Codenzia\FilamentMedia\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class MediaFileController extends Controller
{
    public function postUpload(Request $request): JsonResponse
    {
        try {
            $uploadService = app(UploadService::class);

            if ($this->isChunkedUpload($request)) {
                return $this->handleChunkedUpload($request, $uploadService);
            }

            $file = Arr::first($request->file('file'));

            if (! $file) {
                return FilamentMedia::responseError(__('filament-media::media.no_file_uploaded'));
            }

            $mediaFile = $uploadService->handleUpload($file, $request->input('folder_id', 0));
            $urlService = app(MediaUrlService::class);

            return FilamentMedia::responseSuccess([
                'id' => $mediaFile->id,
                'src' => $urlService->url($mediaFile->url),
            ]);
        } catch (MediaUploadException $exception) {
            return FilamentMedia::responseError($exception->getMessage());
        } catch (Throwable $exception) {
            return FilamentMedia::responseError($exception->getMessage());
        }
    }

    protected function isChunkedUpload(Request $request): bool
    {
        if (! app(UploadService::class)->isChunkUploadEnabled()) {
            return false;
        }

        return $request->hasHeader('X-Chunk-Index')
            || $request->has('dzchunkindex')
            || $request->has('_chunkNumber');
    }

    protected function handleChunkedUpload(Request $request, UploadService $uploadService): JsonResponse
    {
        try {
            $chunkIndex = $request->input('dzchunkindex', $request->header('X-Chunk-Index', 0));
            $totalChunks = $request->input('dztotalchunkcount', $request->header('X-Total-Chunks', 1));
            $uuid = $request->input('dzuuid', $request->header('X-Upload-Id', Str::uuid()->toString()));
            $fileName = $request->input('dzfilename', $request->header('X-File-Name', 'unknown'));

            $file = Arr::first($request->file('file'));

            if (! $file) {
                return FilamentMedia::responseError(__('filament-media::media.no_file_uploaded'));
            }

            $chunkDir = 'chunks/' . $uuid;
            $chunkPath = $chunkDir . '/' . $chunkIndex;

            Storage::disk('local')->put($chunkPath, file_get_contents($file->getRealPath()));

            $uploadedChunks = count(Storage::disk('local')->files($chunkDir));

            if ($uploadedChunks < (int) $totalChunks) {
                return response()->json([
                    'done' => round(($uploadedChunks / (int) $totalChunks) * 100),
                    'status' => true,
                ]);
            }

            $mergedFile = $this->mergeChunks($chunkDir, $fileName, (int) $totalChunks);

            if (! $mergedFile) {
                return FilamentMedia::responseError(__('filament-media::media.failed_to_merge_chunks'));
            }

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($mergedFile) ?: 'application/octet-stream';

            $mediaFile = $uploadService->handleUpload(
                new \Illuminate\Http\UploadedFile(
                    $mergedFile,
                    $fileName,
                    $mimeType,
                    null,
                    true
                ),
                $request->input('folder_id', 0)
            );

            Storage::disk('local')->deleteDirectory($chunkDir);
            @unlink($mergedFile);

            $urlService = app(MediaUrlService::class);

            return FilamentMedia::responseSuccess([
                'id' => $mediaFile->id,
                'src' => $urlService->url($mediaFile->url),
            ]);
        } catch (MediaUploadException $exception) {
            return FilamentMedia::responseError($exception->getMessage());
        } catch (Throwable $exception) {
            return FilamentMedia::responseError($exception->getMessage());
        }
    }

    protected function mergeChunks(string $chunkDir, string $fileName, int $totalChunks): ?string
    {
        $tempFile = storage_path('app/' . $chunkDir . '/' . $fileName);

        $out = fopen($tempFile, 'wb');

        if (! $out) {
            return null;
        }

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = storage_path('app/' . $chunkDir . '/' . $i);

            if (! file_exists($chunkPath)) {
                fclose($out);

                return null;
            }

            $in = fopen($chunkPath, 'rb');

            if (! $in) {
                fclose($out);

                return null;
            }

            while ($buff = fread($in, 4096)) {
                fwrite($out, $buff);
            }

            fclose($in);
        }

        fclose($out);

        return $tempFile;
    }

    public function postUploadFromEditor(Request $request): JsonResponse
    {
        return app(UploadService::class)->uploadFromEditor($request);
    }

    public function postDownloadUrl(Request $request): JsonResponse
    {
        $validator = Validator::make($request->input(), [
            'url' => ['required', 'url'],
            'folderId' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return FilamentMedia::responseError($validator->messages()->first());
        }

        try {
            $uploadService = app(UploadService::class);
            $mediaFile = $uploadService->uploadFromUrl($request->input('url'), $request->input('folderId', 0));

            return FilamentMedia::responseSuccess([
                'id' => $mediaFile->id,
                'src' => Storage::url($mediaFile->url),
                'url' => $mediaFile->url,
                'message' => trans('filament-media::media.javascript.message.success_header'),
            ]);
        } catch (MediaUploadException $exception) {
            return FilamentMedia::responseError($exception->getMessage());
        } catch (Throwable $exception) {
            return FilamentMedia::responseError($exception->getMessage());
        }
    }
}
