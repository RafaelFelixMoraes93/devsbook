<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function() {
    return ['pong'=> true];
});

Route::post('/auth/login', 'AuthController@login');
Route::post('/auth/logout', 'AuthController@logout');
Route::post('/auth/refresh', 'AuthController@refresh');

Route::post('/user', 'AuthController@create');
Route::post('/user', 'AuthController@update');
Route::post('/user/avatar', 'AuthController@updateAvatar');
Route::post('/user/cover', 'UserController@updateCover');
