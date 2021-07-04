<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Controllers;
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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::middleware('auth:api')->group(function (){
    Route::post('/users/logout','UserController@logout');
    Route::get('/users','UserController@index');
    Route::get('/users/search','UserController@show');
    Route::put('/users/{id}','UserController@update');
    Route::delete('/users/{id}','UserController@destroy');
});
Route::get('/address','AddressController@index');
Route::post('/address/search','AddressController@search');
Route::post('/address','AddressController@store');
Route::put('/address/{id}','AddressController@update');
Route::delete('/address/{id}','AddressController@destroy');

Route::get('/category','CategoryController@index');
Route::get('/category/search','CategoryController@show');
Route::post('/category','CategoryController@store');
Route::put('/category/{id}','CategoryController@update');
Route::delete('/category/{id}','CategoryController@destroy');

Route::get('/flag','FlagController@index');
Route::get('/flag/search','FlagController@show');
Route::post('/flag','FlagController@store');
Route::put('/flag/{id}','FlagController@update');
Route::delete('/flag/{id}','FlagController@destroy');

Route::get('/item','ItemController@index');
Route::get('/item/search','ItemController@show');
Route::post('/item','ItemController@store');
Route::put('/item/{id}','ItemController@update');
Route::delete('/item/{id}','ItemController@destroy');

Route::get('/membership','MembershipController@index');
Route::get('/membership/search','MembershipController@show');
Route::post('/membership','MembershipController@store');
Route::put('/membership/{id}','MembershipController@update');
Route::delete('/membership/{id}','MembershipController@destroy');

Route::get('/message','MessageController@index');
Route::get('/message/search','MessageController@show');
Route::post('/message','MessageController@store');
Route::put('/message/{id}','MessageController@update');
Route::delete('/message/{id}','MessageController@destroy');

Route::get('/reporttype','ReportTypeController@index');
Route::get('/reporttype/search','ReportTypeController@show');
Route::post('/reporttype','ReportTypeController@store');
Route::put('/reporttype/{id}','ReportTypeController@update');
Route::delete('/reporttype/{id}','ReportTypeController@destroy');

Route::get('/request','RequestController@index');
Route::get('/request/search','RequestController@show');
Route::post('/request','RequestController@store');
Route::put('/request/{id}','RequestController@update');
Route::delete('/request/{id}','RequestController@destroy');

Route::get('/service','ServiceController@index');
Route::get('/service/search','ServiceController@show');
Route::post('/service','ServiceController@store');
Route::put('/service/{id}','ServiceController@update');
Route::delete('/service/{id}','ServiceController@destroy');

Route::get('/type','TypeController@index');
Route::get('/type/search','TypeController@show');
Route::post('/type','TypeController@store');
Route::put('/type/{id}','TypeController@update');
Route::delete('/type/{id}','TypeController@destroy');