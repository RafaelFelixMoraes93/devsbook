<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function() {
    return ['pong'=> true];
});

Route::post('/auth/login', 'AuthController@login');
