<?php

use App\Http\Controllers\HallOwner\ReseravtionController;
use App\Http\Controllers\User\HallController;
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
use App\Http\Controllers\coordinator\CoordinatorController;
use Illuminate\Http\Request;


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
    Route::get('/events', [UserHallController::class, 'getEventTypes']); // done
    Route::get('/place-types', [UserHallController::class, 'getPlaceTypes']); // done
    Route::get('/place-types/{placeTypeId}/halls', [UserHallController::class, 'getHallsByPlaceType']); // done

    Route::prefix('halls')->group(function () {
    Route::get('/{hallId}', [UserHallController::class, 'show']); // done
    Route::get('services/{hallId}', [UserHallController::class, 'getServicesByHall']); // done
    Route::post('/ratehall/{hallId}', [HallController::class, 'rateHall']); // done    
    Route::get('/{hallId}/available-times', [HallController::class, 'getAvailableTimes']);
});

// الخدمات 
    Route::prefix('services')->group(function () {
    Route::get('service/{id}/categories', [UserHallController::class, 'getServiceCategories']); // done
    Route::get('category/{id}/variants', [UserHallController::class, 'getCategoryVariants']); // done
    Route::get('variant/{id}/types', [UserHallController::class, 'getVariantTypes']); // done

    Route::get('/{hallOwnerId}/music', [UserHallController::class, 'getMusicServiceDetails']); // done
    Route::post('/music/custom-song', [UserHallController::class, 'requestCustomSong']); // done
    Route::get('/{hallOwnerId}/photographers', [UserHallController::class, 'getPhotographers']); // done
    Route::get('/decorations/types', [UserHallController::class, 'getDecorationTypes']); //done 
    Route::get('/decorations/{decorationTypeId}/flowers', [UserHallController::class, 'getFlowersByDecorationType']); // done
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
        Route::post('/food', [UserReservationController::class, 'addorderFood']); // done
        Route::post('/photographer', [UserReservationController::class, 'addassignPhotographer']);  //done
        Route::post('/dj', [UserReservationController::class, 'addassignDJ']);//done
        Route::post('/flowers', [UserReservationController::class, 'addFlowerDecoration']); // done
        Route::get('/summary', [UserReservationController::class, 'getReservationSummary']);  // done
        Route::post('/confirmm', [UserReservationController::class, 'confirmReservationinuser']);    // done
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
    Route::get('/reservations', [ReseravtionController::class, 'getReservationsForHallOwner']);
    Route::get('/reservations/{id}', [ ReseravtionController::class, 'show']);
    Route::get('/reservations/{reservationId}/tasks/{status}', [ReseravtionController::class, 'getTasksByReservationAndStatus']);
    Route::get('/reservations/incomplete/ss', [ReseravtionController::class, 'getOrganizedIncompleteReservations']);
    Route::get('/tasks/{id}', [ReseravtionController::class, 'showTask']);
    Route::post('/confirmReservation/{reservationId}', [ReseravtionController::class, 'confirmReservationinhallowner']);


    // إدارة المنسقين بواسطة مالك الصالة
    Route::prefix('coordinators')->group(function () {
        Route::get('/', [HallOwnerCoordinatorController::class, 'index']); //done عرض جميع المنسقين
        Route::get('/{id}', [HallOwnerCoordinatorController::class, 'show']); //done عرض تفاصيل منسق
        Route::post('/', [HallOwnerCoordinatorController::class, 'store']); //done إضافة منسق جديد
        Route::put('/{id}', [HallOwnerCoordinatorController::class, 'update']); //done 
        Route::delete('/{id}', [HallOwnerCoordinatorController::class, 'destroy']); //done حذف منسق
        Route::post('/assign/{reservationId}', [ReseravtionController::class, 'assignCoordinatorsToReservation']);
        Route::get('/type/{typeId}', [App\Http\Controllers\HallOwner\CoordinatorController::class, 'getByType']);
    }); 

      // إدارة الخدمات وأنواعها الفرعية بواسطة مالك الصالة
    Route::prefix('services')->group(function () {
        Route::get('/hall/{hallId}', [HallOwnerServiceController::class, 'getHallServices']); //done عرض الخدمات المرتبطة بصالة محددة      
        Route::post('/attach', [HallOwnerServiceController::class, 'attachService']); //done ربط خدمة بصالة
        Route::post('/detach', [HallOwnerServiceController::class, 'detachService']); //done  فصل خدمة عن صالة
        Route::post('/Store', [HallOwnerServiceController::class, 'storeService']);
        Route::post('/Sfood-categories', [HallOwnerServiceController::class, 'storeFoodCategory']);
        Route::post('/Sservice-variants', [HallOwnerServiceController::class, 'storeFoodVariants']);
        Route::post('/Sservice-types', [HallOwnerServiceController::class, 'storeFoodTypes']);
        Route::post('/Sdecoration-types', [HallOwnerServiceController::class, 'storeDecorationType']);
        Route::post('/Sflowers', [HallOwnerServiceController::class, 'storeFlower']);
        Route::delete('Dservice/{id}', [HallOwnerServiceController::class, 'deleteService']);
        Route::delete('DdeleteFoodCategory/{id}', [HallOwnerServiceController::class, 'deleteFoodCategory']);
        Route::delete('DdeleteFoodVariant/{id}', [HallOwnerServiceController::class, 'deleteFoodVariant']);
        Route::delete('DdeleteFoodType/{id}', [HallOwnerServiceController::class, 'deleteFoodType']);
        Route::delete('DDecorationType/{id}', [HallOwnerServiceController::class, 'deleteDecorationType']);
        Route::delete('Dflower/{id}', [HallOwnerServiceController::class, 'deleteFlower']);
        });

        });

// =====================================================================================================
// مسارات المنسق (Coordinator) - تتطلب مصادقة ودور 'coordinator' (لشام)
// =====================================================================================================


Route::prefix('coordinator')->middleware(['auth:sanctum', 'coordinator'])->group(function () {
    Route::get('/assignments/pending', [CoordinatorController::class, 'pendingAssignments']);
    Route::get('/assignments/non-pending', [CoordinatorController::class, 'nonPendingAssignments']);
    Route::post('/assignments/{id}/accept', [CoordinatorController::class, 'acceptAssignment']);
    Route::post('/assignments/{id}/reject', [CoordinatorController::class, 'rejectAssignment']);
});



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user(); // ✅ هذا هو الصحيح
});

