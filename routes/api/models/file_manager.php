<?php

use Illuminate\Support\Facades\Route;
use Innoboxrr\AwsFileManager\Http\Controllers\FileManagerController;

Route::get('index', [FileManagerController::class, 'index'])->name('index');

Route::post('upload', [FileManagerController::class, 'upload'])->name('upload');

Route::post('create-directory', [FileManagerController::class, 'createDirectory'])->name('create-directory');

// Pending. Delete directory or delete multiple files in one request
