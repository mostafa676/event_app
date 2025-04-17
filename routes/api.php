<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::post('/signup', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
// Route::post('/logout', [UserController::class, 'logout']);
Route::middleware('auth:sanctum')->post('/logout', [UserController::class, 'logout']);

