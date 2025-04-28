<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\AdminContentController;

Route::post('/signup', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [UserController::class, 'logout']);

Route::get('/event-types', [EventController::class, 'getEventTypes']);
Route::get('/event-types/{id}/halls', [EventController::class, 'getHallsByEvent']);
Route::get('/halls/{id}/services', [EventController::class, 'getServicesByHall']);
Route::get('/services/{serviceId}/variants', [EventController::class, 'getVariantsByService']);

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

Route::middleware('auth:sanctum')->get('/user/search-history', [SearchController::class, 'searchHistory']);
Route::middleware('auth:sanctum')->get('/search/Events', [SearchController::class, 'searchEvents']);
Route::middleware('auth:sanctum')->get('/search/halls', [SearchController::class, 'searchHalls']);
Route::middleware('auth:sanctum')->get('/search/services', [SearchController::class, 'searchServices']);

Route::middleware('auth:sanctum')->post('/user/upload-image', [UserController::class, 'uploadImage']);
Route::middleware('auth:sanctum')->put('/user/update-profile', [UserController::class, 'updateProfile']);
Route::middleware('auth:sanctum')->get('/user/profile', [UserController::class, 'profile']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/reservation/confirm', [ReservationController::class, 'confirmReservation']);
    Route::get('/cart', [ReservationController::class, 'getCart']);
    Route::post('/cart/event-type', [ReservationController::class, 'selectEventType']);
    Route::post('/cart/hall', [ReservationController::class, 'selectHall']);
    Route::post('/cart/add-service', [ReservationController::class, 'addService']);
    Route::delete('/cart/remove-service/{id}', [ReservationController::class, 'removeService']);
    Route::delete('/cart/clear', [ReservationController::class, 'clearCart']);
});

Route::middleware('auth:sanctum')->group(function () {
Route::post('/favorites/add', [FavoriteController::class, 'addToFavorites']);
Route::post('/favorites/remove', [FavoriteController::class, 'removeFromFavorites']);
Route::get('/favorites', [FavoriteController::class, 'listFavorites']);
});