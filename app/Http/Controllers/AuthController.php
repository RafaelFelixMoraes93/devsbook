<?php

namespace App\Http\Controllers;

//use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;

class AuthController extends Controller
{
    public  function __construct() {
        $this->middleware('auth:api', [
        'except' => ['login', 'create', 'unauthorized']
    ]);
    }
}
