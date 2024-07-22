<?php

namespace Innoboxrr\AwsFileManager\Http\Controllers;

use Innoboxrr\AwsFileManager\Http\Requests\FileManager\IndexRequest;
use Innoboxrr\AwsFileManager\Http\Requests\FileManager\UploadRequest;
use Innoboxrr\AwsFileManager\Http\Requests\FileManager\CreateDirectoryRequest;

class FileManagerController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(IndexRequest $request)
    {
        return $request->handle();
    }

    public function upload(UploadRequest $request)
    {
        return $request->handle();
    }

    public function createDirectory(CreateDirectoryRequest $request)
    {
        return $request->handle();
    }
}   
