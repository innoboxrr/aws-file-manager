<?php

use Illuminate\Support\Facades\Route;

Route::post('change-visibility', 'FileController@changeVisibility')->name('change-visibility');

Route::post('delete', 'FileController@delete')->name('delete');