<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function() {
    return ['pong'=> true];
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});