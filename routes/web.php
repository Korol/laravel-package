<?php

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/parser', 'ParserController@index')->name('parser.test');
Route::post('/parser', 'ParserController@parser')->name('parser.post');
Route::get('/parser/{id}', 'ParserController@parseOne')->name('parser.one');