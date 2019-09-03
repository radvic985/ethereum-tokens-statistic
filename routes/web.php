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
Route::get('/clear-cache', function() {
    $exitCode = Artisan::call('cache:clear');
     return "DONE";
});


Route::get('/', 'MainController@index');
Route::get('/search-header', 'AlertsController@searchHeader');
Route::get('/search-whale', 'AlertsController@searchWhale');
Route::get('/search-token', 'AlertsController@searchToken');
Route::post('/submit', 'AlertsController@save')->middleware('auth');
Route::get('/stats', 'StatsController@index');
Route::get('/alerts', 'AlertsController@index')->middleware('auth');
Route::get('/create-alert', 'AlertsController@create')->middleware('auth');
Route::get('/whale/{id}', 'WhalesController@index');
Route::get('/token/{id}', 'TokensController@index');
Route::get('/favorite', 'MainController@favorite');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
Route::get('/debug', 'WhaleController@begin')->name('debug');

Route::group(['prefix' => 'ajax'], function () {
    Route::post('/favorite', 'AjaxController@favorite');
    Route::post('/notice', 'AjaxController@notice');
    Route::post('/search', 'AjaxController@search');
    Route::post('/delete-alert', 'AjaxController@deleteAlert');
    Route::post('/update-alert-view', 'AjaxController@updateAlertView');
    Route::get('/favorite', 'AjaxController@favorite');
});

Route::group(['prefix' => 'whale/ajax'], function () {
    Route::post('/performance', 'AjaxController@performance');
    Route::post('/linechart', 'AjaxController@linechart');
    Route::post('/search', 'AjaxController@search');
});
Route::group(['prefix' => 'token/ajax'], function () {
    Route::post('/search', 'AjaxController@search');
});
Route::group(['prefix' => 'stats/ajax'], function () {
    Route::post('/search', 'AjaxController@search');
});

