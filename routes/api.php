<?php

use App\Http\Controllers\EventController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use PHPUnit\Event\EventCollection;


Route::post('/signup', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [UserController::class, 'logout']);

Route::get('/event-types', [EventController::class, 'getEventTypes']);
Route::get('/event-types/{id}/halls', [EventController::class, 'getHallsByEvent']);
Route::get('/halls/{id}/services', [EventController::class, 'getServicesByHall']);
Route::get('/services/{serviceId}/variants', [EventController::class, 'getVariantsByService']);
