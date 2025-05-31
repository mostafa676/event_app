<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\FavoriteController;
use App\Http\Controllers\User\SearchController;
use App\Http\Controllers\User\HallController as UserHallController; // إعادة تسمية HallController للمستخدم
use App\Http\Controllers\User\ReservationController as UserReservationController; // إعادة تسمية ReservationController للمستخدم
use App\Http\Controllers\Admin\EventTypeController;
use App\Http\Controllers\Admin\HallOwnerController as AdminHallOwnerController; // إعادة تسمية HallOwnerController للمدير العام
use App\Http\Controllers\HallOwner\HallController as HallOwnerHallController; // إعادة تسمية HallController لمالك الصالة
use App\Http\Controllers\HallOwner\ServiceController as HallOwnerServiceController; // سيتم استخدامه لاحقاً
use App\Http\Controllers\HallOwner\CoordinatorController as HallOwnerCoordinatorController; // سيتم استخدامه لاحقاً
use App\Http\Controllers\HallOwner\ReservationController as HallOwnerReservationController; // سيتم استخدامه لاحقاً


// user api 
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/user/profile/image', [AuthController::class, 'uploadImage']);
    Route::post('/user/profile', [AuthController::class, 'updateProfile']); // استخدم POST مع _method=PUT/PATCH
    Route::get('/user/profile', [AuthController::class, 'profile']);

    Route::prefix('search')->group(function () {
        Route::get('events', [SearchController::class, 'searchEvents']);
        Route::get('halls', [SearchController::class, 'searchHalls']);
        Route::get('history', [SearchController::class, 'searchHistory']);
    });

    Route::prefix('halls')->group(function () {
        Route::get('/', [UserHallController::class, 'index']); // عرض جميع الصالات
        Route::get('/{hallId}', [UserHallController::class, 'show']); // عرض تفاصيل صالة محددة
    });

    Route::prefix('events')->group(function () {
        Route::get('/', [UserHallController::class, 'getEventTypes']); // عرض جميع أنواع الأحداث
        Route::get('/{eventTypeId}/halls', [UserHallController::class, 'getHallsByEvent']); // صالات حسب نوع الحدث
    });

    Route::get('/services/all', [UserHallController::class, 'getAllServices']); // جلب جميع الخدمات (عامة)

    Route::prefix('services')->group(function () {
        Route::get('/{serviceId}/variants', [UserHallController::class, 'getVariantsByService']); // أنواع خدمة معينة
    });

    Route::prefix('favorites')->group(function () {
        Route::post('/', [FavoriteController::class, 'addToFavorites']);
        Route::delete('/', [FavoriteController::class, 'removeFromFavorites']);
        Route::get('/', [FavoriteController::class, 'listFavorites']);
    });

    Route::prefix('reservations')->group(function () {
        Route::post('/select-hall', [UserReservationController::class, 'selectHall']);
        Route::post('/add-service', [UserReservationController::class, 'addService']);
        Route::delete('/remove-service/{reservationServiceItemId}', [UserReservationController::class, 'removeService']);
        Route::get('/summary', [UserReservationController::class, 'getReservationSummary']);
        Route::post('/confirm', [UserReservationController::class, 'confirmReservation']);
        Route::put('/{reservationId}', [UserReservationController::class, 'updateReservation']);
        Route::delete('/{reservationId}', [UserReservationController::class, 'cancelReservation']);
        Route::get('/', [UserReservationController::class, 'getUserReservations']);
    });
});

// admin api 
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::prefix('event-types')->group(function () {
        Route::post('/', [EventTypeController::class, 'createEventType']);
        Route::delete('/{id}', [EventTypeController::class, 'deleteEventType']); // تم التعديل إلى ID
        Route::get('/', [EventTypeController::class, 'index']); // عرض جميع أنواع الأحداث
    });

    Route::prefix('hall-owners')->group(function () {
        Route::get('/', [AdminHallOwnerController::class, 'index']); // عرض جميع مالكي الصالات
        Route::get('/by-phone/{phone}', [AdminHallOwnerController::class, 'showByPhone']); // عرض تفاصيل مالك صالة برقم الهاتف
        Route::post('/', [AdminHallOwnerController::class, 'createHallOwner']); // إنشاء مالك صالة
        Route::put('/{id}', [AdminHallOwnerController::class, 'updateHallOwner']); // تحديث مالك صالة
        Route::delete('/{id}', [AdminHallOwnerController::class, 'deleteHallOwner']); // حذف مالك صالة
    });
});

// hall owner api
Route::prefix('hall-owner')->middleware(['auth:sanctum', 'hall_owner'])->group(function () {
    Route::prefix('halls')->group(function () {
        Route::get('/', [HallOwnerHallController::class, 'index']);
        Route::get('/{id}', [HallOwnerHallController::class, 'show']);
        Route::post('/', [HallOwnerHallController::class, 'store']);
        Route::post('/{id}', [HallOwnerHallController::class, 'update']); 
        Route::delete('/{id}', [HallOwnerHallController::class, 'destroy']);
        Route::post('/{hallId}/schedule', [HallOwnerHallController::class, 'updateHallSchedule']);
    });

    Route::prefix('services')->group(function () {
        Route::get('/hall/{hallId}', [HallOwnerServiceController::class, 'getHallServices']); // عرض الخدمات المرتبطة بصالة محددة
        Route::post('/attach', [HallOwnerServiceController::class, 'attachService']); // ربط خدمة بصالة
        Route::post('/detach', [HallOwnerServiceController::class, 'detachService']); // فصل خدمة عن صالة

        Route::get('/{serviceId}/variants', [HallOwnerServiceController::class, 'getServiceVariants']); // عرض أنواع الخدمات الفرعية لخدمة
        Route::post('/variants', [HallOwnerServiceController::class, 'storeVariant']); // إضافة نوع خدمة فرعي
        Route::post('/variants/{variantId}', [HallOwnerServiceController::class, 'updateVariant']); // تحديث نوع خدمة فرعي (استخدم POST مع _method=PUT/PATCH أو PUT مباشرة)
        Route::delete('/variants/{variantId}', [HallOwnerServiceController::class, 'destroyVariant']); // حذف نوع خدمة فرعي
    });

 });

