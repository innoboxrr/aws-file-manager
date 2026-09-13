<?php

use Illuminate\Support\Facades\Route;
use Innoboxrr\AwsFileManager\Http\Controllers\FileController;

Route::post('change-visibility', [FileController::class, 'changeVisibility'])->name('change-visibility');

Route::post('delete', [FileController::class, 'delete'])->name('delete');
