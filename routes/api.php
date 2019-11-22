<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::get('bomb/enter','RecordController@index');
Route::post('bomb/login','RecordController@store');

Route::post('bomb/send','RecordController@game');

Route::post('bomb/start','RecordController@start');
Route::post('bomb/watch','RecordController@watch');
Route::get('bomb/history','RecordController@record');

