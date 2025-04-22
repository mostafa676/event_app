<?php

use App\Http\Controllers\EventController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\AdminContentController;



Route::post('/signup', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [UserController::class, 'logout']);

Route::get('/event-types', [EventController::class, 'getEventTypes']);
Route::get('/event-types/{id}/halls', [EventController::class, 'getHallsByEvent']);
Route::get('/halls/{id}/services', [EventController::class, 'getServicesByHall']);
Route::get('/services/{serviceId}/variants', [EventController::class, 'getVariantsByService']);

Route::middleware('auth:sanctum')->post('/bookings', [BookingController::class, 'createBooking']);
Route::middleware('auth:sanctum')->get('/bookings/{id}', [BookingController::class, 'getBookingDetails']);
Route::middleware('auth:sanctum')->get('/user/bookings', [BookingController::class, 'getUserBookings']);

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/admin/create-event-type', [AdminContentController::class, 'createEventType']);
    Route::post('/admin/create-hall', [AdminContentController::class, 'createHall']);
    Route::post('/admin/create-service', [AdminContentController::class, 'createService']);
    Route::post('/admin/create-service-variant', [AdminContentController::class, 'createServiceVariant']);

    Route::delete('/admin/delete-event-type/{name}', [AdminContentController::class, 'deleteEventTypeByName']);
    Route::delete('/admin/delete-hall/{name}', [AdminContentController::class, 'deleteHallByName']);
    Route::delete('/admin/delete-service/{name}', [AdminContentController::class, 'deleteServiceByName']);
    Route::delete('/admin/delete-service-variant/{name}', [AdminContentController::class, 'deleteServiceVariantByName']);
});
