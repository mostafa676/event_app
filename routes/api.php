<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\FavoriteController;
use App\Http\Controllers\User\SearchController;
use App\Http\Controllers\User\HallController as UserHallController; 
use App\Http\Controllers\User\ReservationController as UserReservationController;
use App\Http\Controllers\Admin\EventTypeController;
use App\Http\Controllers\Admin\HallOwnerController as AdminHallOwnerController;
use App\Http\Controllers\HallOwner\HallController as HallOwnerHallController;
use App\Http\Controllers\HallOwner\ServiceController as HallOwnerServiceController; 
use App\Http\Controllers\HallOwner\CoordinatorController as HallOwnerCoordinatorController; 
use App\Http\Controllers\HallOwner\ReservationController as HallOwnerReservationController; 

// =====================================================================================================
// مسارات المستخدم (User) - عامة (لا تتطلب مصادقة)
// =====================================================================================================

// مسارات المصادقة (التسجيل والدخول) - لا تتطلب مصادقة مسبقة
Route::post('/register', [AuthController::class, 'register']); // done
Route::post('/login', [AuthController::class, 'login']);  // done

// =====================================================================================================
// مسارات المستخدم (User) - تتطلب مصادقة (auth:sanctum)
// =====================================================================================================

Route::middleware('auth:sanctum')->group(function () {
    // مسار تسجيل الخروج
    Route::post('/logout', [AuthController::class, 'logout']);  // done 

    // مسارات ملف المستخدم الشخصي
    Route::prefix('user')->group(function () {
        Route::post('/profile/image', [AuthController::class, 'uploadImage']);  // done
        Route::put('/profile', [AuthController::class, 'updateProfile']); // done
        Route::get('/profile', [AuthController::class, 'profile']); // done
    });

    // مسارات البحث
    Route::prefix('search')->group(function () {
        Route::get('events', [SearchController::class, 'searchEvents']);  // done
        Route::get('halls', [SearchController::class, 'searchHalls']);  // done
        Route::get('history', [SearchController::class, 'searchHistory']);  // done
    });

    // مسارات الصالات والأحداث والخدمات للمستخدم (للعرض والتصفح)
    Route::prefix('halls')->group(function () {
        Route::get('/{hallId}', [UserHallController::class, 'show']); // done
    });

    Route::prefix('events')->group(function () {
        Route::get('/', [UserHallController::class, 'getEventTypes']); // done
        Route::get('/{eventTypeId}/halls', [UserHallController::class, 'getHallsByEvent']);// done
    });

    Route::prefix('services')->group(function () {
        Route::get('/{serviceId}/variants', [UserHallController::class, 'getVariantsByService']); // done
    });

    // مسارات المفضلة للمستخدم
    Route::prefix('favorites')->group(function () {
        Route::post('/', [FavoriteController::class, 'addToFavorites']);  // done
        Route::delete('/', [FavoriteController::class, 'removeFromFavorites']);  // done
        Route::get('/', [FavoriteController::class, 'listFavorites']);  // done
    });

    // مسارات الحجوزات للمستخدم
    Route::prefix('reservations')->group(function () {
        Route::post('/select-hall', [UserReservationController::class, 'selectHall']);  // done
        Route::post('/add-service', [UserReservationController::class, 'addService']);      // بدو تخصيص لكل خدمة لحال يعني كل خدمة بدها تابع لحال يعني api لحال
        Route::delete('/remove-service/{reservationServiceItemId}', [UserReservationController::class, 'removeService']);  // نقس الشي 
        Route::get('/summary', [UserReservationController::class, 'getReservationSummary']);  // done
        Route::post('/confirm', [UserReservationController::class, 'confirmReservation']);    // done
        Route::put('/{reservationId}', [UserReservationController::class, 'updateReservation']);  // done
        Route::delete('/{reservationId}', [UserReservationController::class, 'cancelReservation']);  //done
        Route::get('/', [UserReservationController::class, 'getUserReservations']);  // done
    });
});

// =====================================================================================================
// مسارات المدير العام (Admin) - تتطلب مصادقة ودور 'admin'
// =====================================================================================================

Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::prefix('event-types')->group(function () {
        Route::post('/', [EventTypeController::class, 'createEventType']);   // done 
        Route::delete('/{id}', [EventTypeController::class, 'deleteEventType']); // done
        Route::get('/', [EventTypeController::class, 'index']); // done 
    });

    // إدارة مالكي الصالات بواسطة Admin
    Route::prefix('hall-owners')->group(function () {
        Route::get('/', [AdminHallOwnerController::class, 'index']); // done 
        Route::get('/{id}', [AdminHallOwnerController::class, 'show']); // done 
        Route::post('/', [AdminHallOwnerController::class, 'createHallOwner']); // done
        Route::delete('/{id}', [AdminHallOwnerController::class, 'deleteHallOwner']); // done
    });

    // هنا ستضاف مسارات StatisticsController و DiscountCodeController لاحقاً (لشام)
    // Route::apiResource('discount-codes', \App\Http\Controllers\Admin\DiscountCodeController::class);
    // Route::get('statistics', [\App\Http\Controllers\Admin\StatisticsController::class, 'getGeneralStatistics']);
});

// =====================================================================================================
// مسارات مالك الصالة (Hall Owner) - تتطلب مصادقة ودور 'hall_owner'
// =====================================================================================================

Route::prefix('hall-owner')->middleware(['auth:sanctum', 'hall_owner'])->group(function () {
    // إدارة الصالات بواسطة مالك الصالة
    Route::prefix('halls')->group(function () {
        Route::get('/', [HallOwnerHallController::class, 'index']); // done
        Route::get('/{id}', [HallOwnerHallController::class, 'show']);  // done 
        Route::post('/', [HallOwnerHallController::class, 'store']);  // done
        Route::put('/{id}', [HallOwnerHallController::class, 'update']); //done
        Route::delete('/{id}', [HallOwnerHallController::class, 'destroy']); // done 
        Route::post('/{hallId}/schedule', [HallOwnerHallController::class, 'updateHallSchedule']);  // done
    });

    // إدارة الخدمات وأنواعها الفرعية بواسطة مالك الصالة
    Route::prefix('services')->group(function () {
        Route::get('/hall/{hallId}', [HallOwnerServiceController::class, 'getHallServices']); //done عرض الخدمات المرتبطة بصالة محددة      
        Route::post('/attach', [HallOwnerServiceController::class, 'attachService']); //done ربط خدمة بصالة
        Route::post('/detach', [HallOwnerServiceController::class, 'detachService']); //done  فصل خدمة عن صالة

        Route::get('/{serviceId}/variants', [HallOwnerServiceController::class, 'getServiceVariants']); //done عرض أنواع الخدمات الفرعية لخدمة
        Route::post('/variants', [HallOwnerServiceController::class, 'storeVariant']); //done إضافة نوع خدمة فرعي
        Route::put('/variants/{variantId}', [HallOwnerServiceController::class, 'updateVariant']); //done 
        Route::delete('/variants/{variantId}', [HallOwnerServiceController::class, 'destroyVariant']); //done  حذف نوع خدمة فرعي
    });

    // إدارة المنسقين بواسطة مالك الصالة
    Route::prefix('coordinators')->group(function () {
        Route::get('/', [HallOwnerCoordinatorController::class, 'index']); //done عرض جميع المنسقين
        Route::get('/{id}', [HallOwnerCoordinatorController::class, 'show']); //done عرض تفاصيل منسق
        Route::post('/', [HallOwnerCoordinatorController::class, 'store']); //done إضافة منسق جديد
        Route::put('/{id}', [HallOwnerCoordinatorController::class, 'update']); //done 
        Route::delete('/{id}', [HallOwnerCoordinatorController::class, 'destroy']); //done حذف منسق
    });


});

// =====================================================================================================
// مسارات المنسق (Coordinator) - تتطلب مصادقة ودور 'coordinator' (لشام)
// =====================================================================================================


