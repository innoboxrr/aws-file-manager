<?php

namespace Innoboxrr\AwsFileManager\Http\Controllers;

class FileController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

}
