<?php

namespace Codenzia\FilamentMedia\Http\Controllers;

use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MediaController extends Controller
{
    public function index()
    {
        return view('filament-media::index');
    }

    public function getList(Request $request)
    {
        return FilamentMedia::getList($request);
    }

    public function postCreateFolder(Request $request)
    {
        return FilamentMedia::postCreateFolder($request);
    }

    public function getPopup()
    {
        return view('filament-media::popup');
    }

    public function getDownload(Request $request)
    {
        return FilamentMedia::download($request);
    }

    public function postUploadFile(Request $request)
    {
        return FilamentMedia::postUploadFile($request);
    }

    public function getBreadcrumbs(Request $request)
    {
        return FilamentMedia::getBreadcrumbs($request);
    }

    public function postGlobalActions(Request $request)
    {
        return FilamentMedia::postGlobalActions($request);
    }

    public function postUploadFromEditor(Request $request)
    {
        return FilamentMedia::postUploadFromEditor($request);
    }

    public function postDownloadUrl(Request $request)
    {
        return FilamentMedia::postDownloadUrl($request);
    }
}
