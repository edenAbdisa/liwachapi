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

Route::middleware('auth:api')->group(function () {
   
}); 
//Route::group(['middleware' => ['auth:api','scope:user,admin,organization']], function () { 
//Route::middleware(['auth:api', 'scope:user,admin,organization'])->group(function () {
    Route::get('/user', 'UserController@index');
    Route::get('/users', 'UserController@index');
    Route::post('/user/logout', 'UserController@logout');
    Route::post('/user/search', 'UserController@search');
    Route::put('/user/{id}', 'UserController@update');
    Route::post('/category/search', 'CategoryController@search');
    Route::post('/put', 'MediaController@store');
    Route::get('/flag', 'FlagController@index');
    Route::post('/flag/search', 'FlagController@search');
    Route::post('/item/search', 'ItemController@search');
    Route::get('/media', 'MediaController@index');
    Route::post('/media/search', 'MediaController@search');
    Route::post('/media', 'MediaController@store');
    Route::put('/media/{id}', 'MediaController@update');
    Route::put('/media', 'MediaController@updateAllMedia');
    Route::delete('/media/{id}', 'MediaController@destroy');
    Route::post('/type/search', 'TypeController@search');
    Route::post('/service/search', 'ServiceController@search');
    Route::put('/service/{id}', 'ServiceController@update');
    Route::post('/reporttype/search', 'ReportTypeController@search');    
    Route::put('/flag/{id}', 'FlagController@update');
    Route::put('/item/{id}', 'ItemController@update');    
    Route::delete('/item/{id}', 'ItemController@destroy');
    
    Route::delete('/service/{id}', 'ServiceController@destroy');
//});
Route::group(['middleware' => ['auth:api','scope:organization']], function () {  
});
//Route::group(['middleware' => ['auth:api','scope:user,organization']], function () { 
    Route::delete('/user/{id}', 'UserController@destroy');
    Route::get('/serviceswaptype', 'ServiceSwapTypeController@index');
    Route::get('/itemswaptype', 'ItemSwapTypeController@index');
    Route::post('/flag', 'FlagController@store');
    Route::get('/subscription', 'SubscriptionController@index');
    Route::post('/subscription/search', 'SubscriptionController@search');
    Route::post('/subscription', 'SubscriptionController@store');
    Route::put('/subscription/{id}', 'SubscriptionController@update');
    Route::delete('/subscription/{id}', 'SubscriptionController@destroy');
    Route::get('/message', 'MessageController@index');
    Route::post('/message/search', 'MessageController@search');
    Route::post('/message', 'MessageController@store');
    Route::put('/message/{id}', 'MessageController@update');
    Route::delete('/message/{id}', 'MessageController@destroy');
    Route::get('/request', 'RequestController@index');
    Route::post('/request/search', 'RequestController@search');
    Route::post('/request', 'RequestController@store');
    Route::put('/request/{id}', 'RequestController@update');
    Route::delete('/request/{id}', 'RequestController@destroy');
    Route::post('/service/uploadPicture', 'FileController@uploadFile');
    Route::post('/item', 'ItemController@store');
    Route::post('/service', 'ServiceController@store');
    
//});
//Route::middleware(['auth:api', 'scope:admin'])->group(function () {
//Route::group(['middleware' => ['auth:api','scope:admin']], function () {
 
    Route::get('/address', 'AddressController@index');
    Route::post('/address/search', 'AddressController@search');
    Route::post('/address', 'AddressController@store');
    Route::put('/address/{id}', 'AddressController@update');
    Route::delete('/address/{id}', 'AddressController@destroy');
    Route::get('/user/count', 'UserController@userCount');
    Route::get('/user/countByDate/{attribute}/{start}/{end}', 'UserController@userCountByDate');
    Route::get('/user/internal/{status}', 'UserController@internalUsers');
    Route::get('/user/{status}', 'UserController@userByStatus');
    Route::post('/category', 'CategoryController@store');
    Route::put('/category/{id}', 'CategoryController@update');
    Route::delete('/category/{id}', 'CategoryController@destroy');
    Route::get('/flag/countByDate/{attribute}/{start}/{end}', 'FlagController@flaggedProductCountByDate');
    Route::delete('/flag/{id}', 'FlagController@destroy');
    Route::get('/item/countByDate/{attribute}/{start}/{end}', 'ItemController@itemCountByDate');
    Route::post('/membership', 'MembershipController@store');
    Route::get('/item/countByStatus', 'ItemController@countByStatus');
    Route::post('/membership/search', 'MembershipController@search');
    Route::put('/membership/{id}', 'MembershipController@update');
    Route::delete('/membership/{id}', 'MembershipController@destroy');
    Route::post('/type', 'TypeController@store');
    Route::put('/type/{id}', 'TypeController@update');
    Route::delete('/type/{id}', 'TypeController@destroy');
    Route::get('/service/countByDate/{attribute}/{start}/{end}', 'ServiceController@serviceCountByDate');
    Route::get('/service/countByStatus', 'ServiceController@countByStatus');
    Route::post('/reporttype', 'ReportTypeController@store');
    Route::put('/reporttype/{id}', 'ReportTypeController@update');
    Route::delete('/reporttype/{id}', 'ReportTypeController@destroy');
    Route::get('/request/countByDate/{attribute}/{start}/{end}', 'RequestController@requestCountByDate');
    Route::get('/request/count/{type}', 'RequestController@requestCount');
//});


Route::post('/item/searchAll', 'ItemController@search');
Route::post('/service/searchAll', 'ServiceController@search');
Route::post('/message/searchAll', 'MessageController@search');
Route::post('/request/searchAll', 'RequestController@search');
Route::post('/subscription/searchAll', 'SubscriptionController@search');
Route::post('/flag/searchAll', 'FlagController@search');

Route::post('/user', 'UserController@store');
Route::post('/user/login', 'UserController@login');
Route::post('/service/byLocation', 'ServiceController@serviceByLocation');
Route::post('/item/byLocation', 'ItemController@itemsByLocation');
Route::get('/item', 'ItemController@index');
Route::get('/service', 'ServiceController@index');
Route::get('/type', 'TypeController@index');    
Route::get('/reporttype', 'ReportTypeController@index');
Route::get('/category', 'CategoryController@index');
Route::get('/membership', 'MembershipController@index');
//Route::get('/request/{status}/{type}', 'RequestController@statusCountRequest'); 
