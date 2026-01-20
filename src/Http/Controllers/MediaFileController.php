<?php

namespace Codenzia\FilamentMedia\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Media\Chunks\Exceptions\UploadMissingFileException;
use Botble\Media\Chunks\Handler\DropZoneUploadHandler;
use Botble\Media\Chunks\Receiver\FileReceiver;
use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;
use Illuminate\Routing\Controller;

/**
 * @since 19/08/2015 07:50 AM
 */
class MediaFileController extends Controller
{
    public function postUpload(Request $request)
    {
        try {
            if (! FilamentMedia::isChunkUploadEnabled()) {
                $result = FilamentMedia::handleUpload(Arr::first($request->file('file')), $request->input('folder_id', 0));

                return $this->handleUploadResponse($result);
            }

            // Create the file receiver
            $receiver = new FileReceiver('file', $request, DropZoneUploadHandler::class);
            // Check if the upload is success, throw exception or return response you need
            if ($receiver->isUploaded() === false) {
                throw new UploadMissingFileException();
            }
            // Receive the file
            $save = $receiver->receive();
            // Check if the upload has finished (in chunk mode it will send smaller files)
            if ($save->isFinished()) {
                $result = FilamentMedia::handleUpload($save->getFile(), $request->input('folder_id', 0));

                return $this->handleUploadResponse($result);
            }
            // We are in chunk mode, lets send the current progress
            $handler = $save->handler();

            return response()->json([
                'done' => $handler->getPercentageDone(),
                'status' => true,
            ]);
        } catch (Throwable $exception) {
            return FilamentMedia::responseError($exception->getMessage());
        }
    }

    protected function handleUploadResponse(array $result): JsonResponse
    {
        if (! $result['error']) {
            return FilamentMedia::responseSuccess([
                'id' => $result['data']->id,
                'src' => FilamentMedia::url($result['data']->url),
            ]);
        }

        return FilamentMedia::responseError($result['message']);
    }

    public function postUploadFromEditor(Request $request)
    {
        return FilamentMedia::uploadFromEditor($request);
    }

    public function postDownloadUrl(Request $request)
    {
        $validator = Validator::make($request->input(), [
            'url' => ['required', 'url'],
            'folderId' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return FilamentMedia::responseError($validator->messages()->first());
        }

        $result = FilamentMedia::uploadFromUrl($request->input('url'), $request->input('folderId', 0));

        if (! $result['error']) {
            return FilamentMedia::responseSuccess([
                'id' => $result['data']->id,
                'src' => Storage::url($result['data']->url),
                'url' => $result['data']->url,
                'message' => trans('core/media::media.javascript.message.success_header'),
            ]);
        }

        return FilamentMedia::responseError($result['message']);
    }
}
