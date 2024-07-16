<?php

namespace Innoboxrr\AwsFileManager\Http\Controllers;

use Innoboxrr\AwsFileManager\Http\Requests\File\ChangeVisibilityRequest;
use Innoboxrr\AwsFileManager\Http\Requests\File\DeleteFileRequest;

class FileController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function changeVisibility(ChangeVisibilityRequest $request)
    {
        return $request->handle();
    }

    public function delete(DeleteFileRequest $request)
    {
        return $request->handle();
    }

}
