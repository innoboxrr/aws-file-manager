<?php

use Illuminate\Support\Facades\Route;

Route::get('index', 'FileManagerController@index')->name('index');

Route::post('upload', 'FileManagerController@upload')->name('upload');

Route::post('create-directory', 'FileManagerController@createDirectory')->name('create-directory');