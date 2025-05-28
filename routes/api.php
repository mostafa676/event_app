<?php

use App\Http\Controllers\User\HallController;
use App\Http\Controllers\User\ReservationController;
use App\Http\Controllers\User\SearchController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\AuthController; 
use App\Http\Controllers\User\FavoriteController;
use App\Http\Controllers\AdminContentController;
use PhpParser\Builder\Use_;
use App\Http\Controllers\Admin\EventTypeController;


Route::post('/signup', [AuthController::class, 'register']);//
Route::post('/login', [AuthController::class, 'login']);//
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);//

Route::get('/event-types', [HallController::class, 'getEventTypes']);//
Route::get('/event-types/{id}/halls', [HallController::class, 'getHallsByEvent']);//
Route::get('/halls/{id}/services', [HallController::class, 'getServicesByHall']);//
Route::get('/services/{serviceId}/variants', [HallController::class, 'getVariantsByService']);//

Route::middleware(['auth:sanctum', 'admin'])->group(callback: function () { 
    Route::post('/admin/create-event-type', [EventTypeController::class, 'createEventType']);//
    // Route::post('/admin/create-hall', [AdminContentController::class, 'createHall']);//
    // Route::post('/admin/create-service', [AdminContentController::class, 'createService']);//
    // Route::post('/admin/create-service-variant', [AdminContentController::class, 'createServiceVariant']);//

    Route::delete('/admin/delete-event-type/{name}', [EventTypeController::class, 'deleteEventType']);//
    // Route::delete('/admin/delete-hall/{name}', [AdminContentController::class, 'deleteHallByName']);//
    // Route::delete('/admin/delete-service/{name}', [AdminContentController::class, 'deleteServiceByName']);//
    // Route::delete('/admin/delete-service-variant/{name}', [AdminContentController::class, 'deleteServiceVariantByName']);//
});

Route::middleware('auth:sanctum')->get('/user/search-history', [SearchController::class, 'searchHistory']);//
Route::middleware('auth:sanctum')->get('/search/Events', [SearchController::class, 'searchEvents']);//
Route::middleware('auth:sanctum')->get('/search/halls', [SearchController::class, 'searchHalls']);//

Route::middleware('auth:sanctum')->post('/user/upload-image', [AuthController::class, 'uploadImage']);//
Route::middleware('auth:sanctum')->put('/user/update-profile', [AuthController::class, 'updateProfile']);//
Route::middleware('auth:sanctum')->get('/user/profile', [AuthController::class, 'profile']);//

Route::prefix('reservations')->group(function () {
        Route::post('/select-hall', [ReservationController::class, 'selectHall']);
        Route::post('/add-service', [ReservationController::class, 'addService']);
        Route::delete('/remove-service/{reservationServiceItemId}', [ReservationController::class, 'removeService']);
        Route::get('/summary', [ReservationController::class, 'getReservationSummary']);
        Route::post('/confirm', [ReservationController::class, 'confirmReservation']);
        Route::put('/{reservationId}', [ReservationController::class, 'updateReservation']); // لتعديل الحجز
        Route::delete('/{reservationId}', [ReservationController::class, 'cancelReservation']); // لإلغاء الحجز
        Route::get('/', [ReservationController::class, 'getUserReservations']); // عرض جميع حجوزات المستخدم
    });

Route::middleware('auth:sanctum')->group(function () {
Route::post('/favorites/add', [FavoriteController::class, 'addToFavorites']);//
Route::post('/favorites/remove', [FavoriteController::class, 'removeFromFavorites']);//
Route::get('/favorites', [FavoriteController::class, 'listFavorites']);//
});