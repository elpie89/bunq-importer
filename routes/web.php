<?php

declare(strict_types=1);
/**
 * web.php
 * Copyright (c) 2020 james@firefly-iii.org.
 *
 * This file is part of the Firefly III bunq importer
 * (https://github.com/firefly-iii/bunq-importer).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/', ['uses' => 'IndexController@index', 'as' => 'index']);

// clear session
Route::get('/flush', 'IndexController@flush')->name('flush');

// validate access token:
Route::get('/token', 'TokenController@index')->name('token.index');
Route::get('/token/validate', 'TokenController@doValidate')->name('token.validate');

// start bunq import.
Route::get('/import/start', ['uses' => 'Import\StartController@index', 'as' => 'import.start']);
Route::post('/import/upload', ['uses' => 'Import\UploadController@upload', 'as' => 'import.upload']);

// configure
Route::get('/import/configure', ['uses' => 'Import\ConfigurationController@index', 'as' => 'import.configure.index']);
Route::post('/import/configure', ['uses' => 'Import\ConfigurationController@postIndex', 'as' => 'import.configure.post']);

// download from bunq
Route::get('/import/download', ['uses' => 'Import\DownloadController@index', 'as' => 'import.download.index']);
Route::any('/import/download/start', ['uses' => 'Import\DownloadController@start', 'as' => 'import.download.start']);
Route::get('/import/download/status', ['uses' => 'Import\DownloadController@status', 'as' => 'import.download.status']);

// do mapping configuration
Route::get('/import/mapping', ['uses' => 'Import\MappingController@index', 'as' => 'import.mapping.index']);
Route::post('/import/mapping', ['uses' => 'Import\MappingController@postIndex', 'as' => 'import.mapping.post']);

// upload
Route::get('/import/sync', ['uses' => 'Import\SyncController@index', 'as' => 'import.sync.index']);
Route::any('/import/sync/start', ['uses' => 'Import\SyncController@start', 'as' => 'import.sync.start']);
Route::get('/import/sync/status', ['uses' => 'Import\SyncController@status', 'as' => 'import.sync.status']);

// download config:
Route::get('/configuration/download', ['uses' => 'Import\ConfigurationController@download', 'as' => 'import.configuration.download']);
