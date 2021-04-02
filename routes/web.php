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

Route::get('/parser/test', 'ParserController@index')->name('parser.test');
Route::get('/parser/run', 'ParserController@parser')->name('parser.run');
Route::get('/parser/{id}', 'ParserController@parseOne')->name('parser.one');