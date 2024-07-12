<?php

namespace Innoboxrr\AwsFileManager\Http\Controllers;

use Innoboxrr\AwsFileManager\Http\Requests\FileManager\IndexRequest;

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
}   
