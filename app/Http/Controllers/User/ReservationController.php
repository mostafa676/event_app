<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Flower;
use App\Models\Hall;
use App\Models\HallSchedule;
use App\Models\Service;
use App\Models\ServiceVariant;
use App\Models\Reservation; // نموذج الحجز
use App\Models\ReservationService; // نموذج خدمات الحجز
use App\Models\Coordinator; // المشرفين (المنسقين)
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon; // لاستخدام التواريخ والأوقات بسهولة
use App\Models\ServiceType;
USE App\Helpers\NotificationHelper;



class ReservationController extends Controller
{
//   public function selectHall(Request $request)
// {
//     try {
//         $request->validate([
//             'hall_id' => 'required|exists:halls,id',
//             'reservation_date' => 'required|date_format:Y-m-d',
//             'start_time' => 'required|date_format:H:i',
//             'end_time' => 'required|date_format:H:i|after:start_time',
//         ]);

//         $hallId = $request->hall_id;
//         $reservationDate = $request->reservation_date;
//         $startTime = $request->start_time;
//         $endTime = $request->end_time;

//         // ✅ 1. تحقق من جدول المواعيد للصالة
//         $dayOfWeek = Carbon::parse($reservationDate)->format('l'); // Monday, Tuesday, etc.

//         $scheduleAvailable = HallSchedule::where('hall_id', $hallId)
//             ->where('day_of_week', $dayOfWeek)
//             ->where('is_available', true)
//             ->where('start_time', '<=', $startTime)
//             ->where('end_time', '>=', $endTime)
//             ->exists();

//         if (!$scheduleAvailable) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'الوقت المختار غير متاح في جدول مواعيد الصالة لهذا اليوم.',
//             ], 400);
//         }

//         // ✅ 2. تحقق من عدم وجود تعارض بالحجوزات
//         $conflict = Reservation::where('hall_id', $hallId)
//             ->where('reservation_date', $reservationDate)
//             ->whereIn('status', ['confirmed', 'pending'])
//             ->where(function ($query) use ($startTime, $endTime) {
//                 $query->where('start_time', '<', $endTime)
//                       ->where('end_time', '>', $startTime);
//             })
//             ->exists();

//         if ($conflict) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'عذراً، هذه الفترة الزمنية محجوزة بالفعل للصالة المطلوبة أو هناك حجز معلق.',
//             ], 400);
//         }

//         // ✅ 3. إنشاء أو تحديث الحجز المؤقت
//         $reservation = Reservation::firstOrCreate(
//             ['user_id' => auth()->id(), 'status' => 'pending'],
//             [
//                 'hall_id' => $hallId,
//                 'reservation_date' => $reservationDate,
//                 'start_time' => $startTime,
//                 'end_time' => $endTime,
//                 'event_type_id' => Hall::find($hallId)->event_type_id,
//                 'status' => 'pending',
//                 'total_price' => 0
//             ]
//         );

//         if (!$reservation->wasRecentlyCreated) {
//             $reservation->update([
//                 'hall_id' => $hallId,
//                 'reservation_date' => $reservationDate,
//                 'start_time' => $startTime,
//                 'end_time' => $endTime,
//                 'event_type_id' => Hall::find($hallId)->event_type_id,
//             ]);
//             $reservation->reservationServices()->delete();
//         }

//         return response()->json([
//             'status' => true,
//             'message' => 'تم اختيار الصالة وتحديث الحجز المؤقت بنجاح.',
//             'reservation_id' => $reservation->id,
//             'reservation_status' => $reservation->status,
//         ], 200);

//     } catch (ValidationException $e) {
//         Log::error('Validation error in selectHall: ' . $e->getMessage(), ['errors' => $e->errors()]);
//         return response()->json([
//             'status' => false,
//             'message' => 'فشل التحقق من صحة البيانات.',
//             'errors' => $e->errors(),
//         ], 422);
//     } catch (\Exception $e) {
//         Log::error('Error in selectHall: ' . $e->getMessage());
//         return response()->json([
//             'status' => false,
//             'message' => 'حدث خطأ أثناء اختيار الصالة.',
//             'error' => $e->getMessage(),
//         ], 500);
//     }
// }


public function selectHall(Request $request)
{
    try {
        $request->validate([
            'hall_id' => 'required|exists:halls,id',
            'reservation_date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $hallId = $request->hall_id;
        $reservationDate = $request->reservation_date;
        $startTime = $request->start_time . ':00';
        $endTime = $request->end_time . ':00';

        // ضبط صيغة اليوم
        $dayOfWeek = strtolower(Carbon::parse($reservationDate)->format('l'));

        // تحقق من أن الوقت داخل الجدول
        $scheduleAvailable = HallSchedule::where('hall_id', $hallId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_available', true)
            ->where('start_time', '<=', $startTime)
            ->where('end_time', '>=', $endTime)
            ->exists();

        if (!$scheduleAvailable) {
            return response()->json([
                'status' => false,
                'message' => 'الوقت المختار غير متاح في جدول مواعيد الصالة لهذا اليوم.',
            ], 400);
        }

        // تحقق من عدم وجود تعارض بالحجوزات
        $conflict = Reservation::where('hall_id', $hallId)
            ->where('reservation_date', $reservationDate)
            ->whereIn('status', ['confirmed', 'pending'])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })
            ->exists();

        if ($conflict) {
            return response()->json([
                'status' => false,
                'message' => 'عذراً، هذه الفترة الزمنية محجوزة بالفعل أو هناك حجز معلق.',
            ], 400);
        }

        // إنشاء أو تحديث الحجز المؤقت
        $reservation = Reservation::firstOrCreate(
            ['user_id' => auth()->id(), 'status' => 'pending'],
            [
                'hall_id' => $hallId,
                'reservation_date' => $reservationDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'event_type_id' => Hall::find($hallId)->event_type_id,
                'status' => 'pending',
                'total_price' => 0
            ]
        );

        if (!$reservation->wasRecentlyCreated) {
            $reservation->update([
                'hall_id' => $hallId,
                'reservation_date' => $reservationDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'event_type_id' => Hall::find($hallId)->event_type_id,
            ]);
            $reservation->reservationServices()->delete();
        }

        return response()->json([
            'status' => true,
            'message' => 'تم اختيار الصالة وتحديث الحجز المؤقت بنجاح.',
            'reservation_id' => $reservation->id,
            'reservation_status' => $reservation->status,
        ], 200);

    } catch (ValidationException $e) {
        return response()->json([
            'status' => false,
            'message' => 'فشل التحقق من صحة البيانات.',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء اختيار الصالة.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function addorderFood(Request $request)
{
    $request->validate([
        'reservation_id' => 'required|exists:reservations,id',
        'items' => 'required|array',
        'items.*.service_type_id' => 'required|exists:service_types,id',
        'items.*.quantity' => 'required|integer|min:1',
    ]);

    try {
        foreach ($request->items as $item) {
            $type = ServiceType::findOrFail($item['service_type_id']);

            ReservationService::create([
                'reservation_id' => $request->reservation_id,
                'service_id' => $type->variant->category->service_id,
                'service_category_id' => $type->service_category_id,
                'quantity' => $item['quantity'],
                'unit_price' => $type->price,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'تمت إضافة الطعام بنجاح إلى الحجز.',
        ]);
    } catch (\Exception $e) {
        \Log::error('Error in orderFood: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء إضافة الطعام.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function addassignPhotographer(Request $request)
{
    $request->validate([
        'reservation_id' => 'required|exists:reservations,id',
        'coordinator_id' => 'required|exists:coordinators,id',
        'service_id' => 'required|exists:services,id', // يجب أن يكون تصوير
        'service_variant_id' => 'nullable|exists:service_variants,id',
        'unit_price' => 'required|numeric|min:0',
    ]);

    try {
        ReservationService::create([
            'reservation_id' => $request->reservation_id,
            'service_id' => $request->service_id,
            'service_variant_id' => $request->service_variant_id,
            'coordinator_id' => $request->coordinator_id,
            'quantity' => 1,
            'unit_price' => $request->unit_price,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم حجز المصور بنجاح.'
        ]);
    } catch (\Exception $e) {
        \Log::error('assignPhotographer Error: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'فشل في حجز المصور.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function addassignDJ(Request $request)
{
    $request->validate([
        'reservation_id' => 'required|exists:reservations,id',
        'coordinator_id' => 'required|exists:coordinators,id',
        'service_id' => 'required|exists:services,id',
        'unit_price' => 'required|numeric|min:0',
        'song_ids' => 'nullable|array',
        'song_ids.*' => 'exists:songs,id',
        'custom_songs' => 'nullable|array',
        'custom_songs.*.title' => 'required|string',
        'custom_songs.*.artist' => 'nullable|string',
    ]);

    try {
        // حفظ DJ
        ReservationService::create([
            'reservation_id' => $request->reservation_id,
            'service_id' => $request->service_id,
            'coordinator_id' => $request->coordinator_id,
            'quantity' => 1,
            'unit_price' => $request->unit_price,
        ]);

        // حفظ الأغاني العادية
        if ($request->filled('song_ids')) {
            foreach ($request->song_ids as $songId) {
                ReservationService::create([
                    'reservation_id' => $request->reservation_id,
                    'service_id' => $request->service_id,
                    'song_id' => $songId,
                    'quantity' => 1,
                    'unit_price' => 0,
                ]);
            }
        }

        // حفظ الأغاني المخصصة
        if ($request->filled('custom_songs')) {
            foreach ($request->custom_songs as $custom) {
                ReservationService::create([
                    'reservation_id' => $request->reservation_id,
                    'service_id' => $request->service_id,
                    'custom_song_title' => $custom['title'],
                    'custom_song_artist' => $custom['artist'] ?? null,
                    'quantity' => 1,
                    'unit_price' => 0,
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'تم حجز DJ والأغاني بنجاح.'
        ]);
    } catch (\Exception $e) {
        \Log::error('assignDJ Error: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'فشل في حجز DJ أو الأغاني.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function addFlowerDecoration(Request $request)
{
    $request->validate([
        'reservation_id' => 'required|exists:reservations,id',
        'flower_id' => 'required|exists:flowers,id',
        'location' => 'required|string',
        'quantity' => 'required|integer|min:1',
        'color' => 'required|string',
        'unit_price' => 'required|numeric|min:0',
    ]);

    try {
        $flower = Flower::findOrFail($request->flower_id);

        ReservationService::create([
            'reservation_id' => $request->reservation_id,
            'service_id' => $flower->decorationType->service_id,
            'service_variant_id' => $flower->decoration_type_id,
            'quantity' => $request->quantity,
            'unit_price' => $request->unit_price,
            'location' => $request->location, // استخدام location هنا كمكان الزينة
            'color' => $request->color // استخدام color هنا
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تمت إضافة الزينة بنجاح.'
        ]);
    } catch (\Exception $e) {
        \Log::error('addFlowerDecoration Error: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء حجز الزينة.',
            'error' => $e->getMessage()
        ], 500);
    }
}




// nnn 
public function confirmReservationinuser(Request $request)
{
    try {
        $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'home_address' => 'nullable|string|max:255',
            'discount_code' => 'nullable|string|exists:discount_codes,code', 
        ]);

        $user = auth()->user();
        $reservation = Reservation::where('id', $request->reservation_id)
                                ->where('user_id', $user->id)
                                ->where('status', 'pending')
                                ->first();

        if (!$reservation) {
            return response()->json([
                'status' => false,
                'message' => 'الحجز المؤقت غير موجود أو لا يمكنك تأكيده.',
            ], 404);
        }
//&& $reservation->reservationServices->isEmpty()
        if (!$reservation->hall_id ) {
            return response()->json([
                'status' => false,
                'message' => 'لا يمكن تأكيد حجز فارغ. يرجى اختيار صالة أو خدمات أولاً.',
            ], 400);
        }

        $finalPrice = $this->calculateFinalPrice($reservation); 
        if ($request->filled('discount_code')) {
            $discountCode = \App\Models\DiscountCode::where('code', $request->discount_code)
                                                    ->where('is_active', true)
                                                    ->first();
            if ($discountCode) {
                $discountAmount = ($finalPrice * $discountCode->discount_percentage) / 100;
                $finalPrice -= $discountAmount;
                $reservation->discount_code_id = $discountCode->id; 
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'كود الخصم غير صالح أو غير نشط.',
                ], 400);
            }
        }

        $reservation->total_price = $finalPrice;
        $reservation->status = 'confirmed';
        $reservation->home_address = ($reservation->hall_id == 9999 && $request->filled('home_address')) ? $request->home_address : null;
        $reservation->save();
        // إرسال إشعار لصاحب الصالة
        if ($reservation->hall && $reservation->hall->user) {
            NotificationHelper::sendFCM(
                $reservation->hall->user,
                'reservation_confirmed',
                'تم تأكيد الحجز',
                'قام ' . $user->name . ' بتأكيد الحجز في صالتك.',
                [
                    'reservation_id' => $reservation->id,
                    'notifiable_id' => $reservation->id,
                    'notifiable_type' => Reservation::class
                ]
            );
        }

        return response()->json([
            'status' => true,
            'message' => "تم تأكيد الحجز بنجاح. سيتم إرسال رسالة تأكيد عند انتهاء الطلب",
            'reservation' => $reservation,
        ], 201); 

    } catch (ValidationException $e) {
        return response()->json([
            'status' => false,
            'message' => 'فشل التحقق من صحة البيانات.',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء تأكيد الحجز.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


public function getReservationSummary()
    {
        try {
            $user = auth()->user();
            $reservation = Reservation::with([
                'hall',
                'eventType',
                'reservationServices.service',
                'reservationServices.variant'
            ])
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

            if (!$reservation) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا يوجد حجز مؤقت حالياً.',
                    'summary' => null,
                ], 404);
            }

            $totalPrice = 0;
            if ($reservation->hall) {
                $totalPrice += $reservation->hall->price;
            }

            $servicesDetails = $reservation->reservationServices->map(function ($item) use (&$totalPrice) {
                $itemTotalPrice = $item->quantity * $item->unit_price;
                $totalPrice += $itemTotalPrice;
                return [
                    'id' => $item->id,
                    'service_name_ar' => $item->service->name_ar ?? null,
                    'service_name_en' => $item->service->name_en ?? null,
                    'variant_name_ar' => $item->serviceVariant->name_ar ?? null,
                    'variant_name_en' => $item->serviceVariant->name_en ?? null,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_item_price' => $itemTotalPrice,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'تم جلب ملخص الحجز بنجاح.',
                'summary' => [
                    'reservation_id' => $reservation->id,
                    'reservation_date' => $reservation->reservation_date,
                    'start_time' => $reservation->start_time,
                    'end_time' => $reservation->end_time,
                    'hall' => $reservation->hall ? [
                        'id' => $reservation->hall->id,
                        'name_ar' => $reservation->hall->name_ar,
                        'name_en' => $reservation->hall->name_en,
                        'price' => $reservation->hall->price,
                    ] : null,
                    'event' => $reservation->event ? [
                        'id' => $reservation->event->id,
                        'name_ar' => $reservation->event->name_ar,
                        'name_en' => $reservation->event->name_en,
                    ] : null,
                    'services' => $servicesDetails,
                    'total_price' => $totalPrice,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in getReservationSummary: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب ملخص الحجز.',
                'error' => $e->getMessage(),
            ], 500);
        }
}

    // * متاح فقط للحجوزات المعلقة أو خلال 24 ساعة من الحجز.
public function updateReservation(Request $request, $reservationId)
    {
        try {
            $user = auth()->user();
            $reservation = Reservation::where('id', $reservationId)
                                    ->where('user_id', $user->id)
                                    ->first();

            if (!$reservation) {
                return response()->json([
                    'status' => false,
                    'message' => 'الحجز غير موجود أو لا تملك صلاحية تعديله.',
                ], 404);
            }
            $canModify = ($reservation->status === 'pending') ||
                         (Carbon::parse($reservation->created_at)->addHours(24)->isFuture() && $reservation->status === 'confirmed');

            if (!$canModify) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا يمكن تعديل هذا الحجز حالياً.',
                ], 403);
            }

            $request->validate([
                'services' => 'nullable|array',
                'services.*.reservation_service_id' => 'required|exists:reservation_services,id',
                'services.*.quantity' => 'required|integer|min:1',
            ]);

            if ($request->has('services')) {
                foreach ($request->services as $serviceData) {
                    $item = ReservationService::where('id', $serviceData['reservation_service_id'])
                                            ->where('reservation_id', $reservation->id)
                                            ->first();
                    if ($item) {
                        $item->update(['quantity' => $serviceData['quantity']]);
                    }
                }
            }
            $reservation->total_price = $this->calculateFinalPrice($reservation);
            $reservation->save();

            $reservation->load(['hall', 'eventType', 'reservationServices.service', 'reservationServices.variant']);

            return response()->json([
                'status' => true,
                'message' => 'تم تحديث الحجز بنجاح.',
                'reservation' => $reservation,
            ], 200);

        } catch (ValidationException $e) {
            Log::error('Validation error in updateReservation: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in updateReservation: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تحديث الحجز.',
                'error' => $e->getMessage(),
            ], 500);
        }
}

    // * متاح فقط للحجوزات المعلقة أو خلال 24 ساعة من الحجز.
    public function cancelReservation($reservationId)
    {
        try {
            $user = auth()->user();
            $reservation = Reservation::where('id', $reservationId)
                                    ->where('user_id', $user->id)
                                    ->first();

            if (!$reservation) {
                return response()->json([
                    'status' => false,
                    'message' => 'الحجز غير موجود أو لا تملك صلاحية إلغائه.',
                ], 404);
            }
            $canCancel = ($reservation->status === 'pending') ||
                         (Carbon::parse($reservation->created_at)->addHours(24)->isFuture() && $reservation->status === 'confirmed');

            if (!$canCancel) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا يمكن إلغاء هذا الحجز حالياً. يرجى التواصل مع الإدارة.',
                ], 403);
            }

            $reservation->update(['status' => 'cancelled']);
            return response()->json([
                'status' => true,
                'message' => 'تم إلغاء الحجز بنجاح.',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in cancelReservation: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إلغاء الحجز.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getUserReservations()
    {
        try {
            $user = auth()->user();
            $reservations = Reservation::with([
                'hall',
                'eventType',
                'reservationServices.service',
                'reservationServices.variant'
            ])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

            if ($reservations->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا توجد حجوزات سابقة.',
                    'reservations' => [],
                ], 200);
            }

            return response()->json([
                'status' => true,
                'message' => 'تم جلب الحجوزات بنجاح.',
                'reservations' => $reservations,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in getUserReservations: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب الحجوزات.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

   private function calculateFinalPrice(Reservation $reservation)
{
    $totalPrice = 0;

    // 1. سعر القاعة (إذا موجودة)
    if ($reservation->relationLoaded('hall') || $reservation->hall) {
        $totalPrice += $reservation->hall->price ?? 0;
    }

    // 2. أسعار الخدمات المضافة للحجز (إذا موجودة)
    if ($reservation->relationLoaded('reservationServices') || $reservation->reservationServices()->exists()) {
        foreach ($reservation->reservationServices as $item) {
            $totalPrice += ($item->quantity ?? 0) * ($item->unit_price ?? 0);
        }
    }

    // 3. أسعار المنسقين المعينين (إذا موجودين)
    if ($reservation->relationLoaded('coordinatorAssignments') || $reservation->coordinatorAssignments()->exists()) {
        foreach ($reservation->coordinatorAssignments as $assignment) {
            $totalPrice += $assignment->total_cost ?? 0;
        }
    }

    // // 4. أسعار الزينة (تم تعطيلها لأنه ما في علاقة)
    
    // if ($reservation->relationLoaded('flowerPlacements') || $reservation->flowerPlacements()->exists()) {
    //     foreach ($reservation->flowerPlacements as $placement) {
    //         if ($placement->flower) {
    //             $totalPrice += ($placement->quantity ?? 0) * ($placement->flower->price ?? 0);
    //         }
    //     }
    // }

    return $totalPrice;
}

// public function confirmReservation(Request $request)
//     {
//         try {
//             $request->validate([
//                 'reservation_id' => 'required|exists:reservation,id',
//                 'home_address' => 'nullable|string|max:255', // مطلوب فقط إذا كانت الصالة 9999
//                 'discount_code' => 'nullable|string|exists:discount_codes,code', 
//             ]);

//             $user = auth()->user();
//             $reservation = Reservation::where('id', $request->reservation_id)
//                                     ->where('user_id', $user->id)
//                                     ->where('status', 'pending')
//                                     ->first();

//             if (!$reservation) {
//                 return response()->json([
//                     'status' => false,
//                     'message' => 'الحجز المؤقت غير موجود أو لا يمكنك تأكيده.',
//                 ], 404);
//             }
//             if (!$reservation->hall_id && $reservation->reservationServices->isEmpty()) {
//                  return response()->json([
//                     'status' => false,
//                     'message' => 'لا يمكن تأكيد حجز فارغ. يرجى اختيار صالة أو خدمات أولاً.',
//                 ], 400);
//             }

//             $finalPrice = $this->calculateFinalPrice($reservation); 
//                if ($request->filled('discount_code')) {
//                 $discountCode = \App\Models\DiscountCode::where('code', $request->discount_code)
//                                                         ->where('is_active', true)
//                                                         ->first();
//                 if ($discountCode) {
//                     // تطبيق الخصم
//                     $discountAmount = ($finalPrice * $discountCode->discount_percentage) / 100;
//                     $finalPrice -= $discountAmount;
//                     $reservation->discount_code_id = $discountCode->id; 
//                 } else {
//                     return response()->json([
//                         'status' => false,
//                         'message' => 'كود الخصم غير صالح أو غير نشط.',
//                     ], 400);
//                 }
//             }
//             $reservation->total_price = $finalPrice;
//             $reservation->update([
//                 'status' => 'confirmed',
//                 'home_address' => ($reservation->hall_id == 9999 && $request->filled('home_address')) ? $request->home_address : null,
//             ]);
//             $reservation->load(['hall', 'eventType', 'reservationServices.service', 'reservationServices.serviceVariant']);
//             return response()->json([
//                 'status' => true,
//                 'message' => "تم تأكيد الحجز بنجاح. سيتم إرسال رسالة تأكيد عند انتهاء الطلب",
//                 'reservation' => $reservation,
//             ], 201); 

//         } catch (ValidationException $e) {
//             Log::error('Validation error in confirmReservation: ' . $e->getMessage(), ['errors' => $e->errors()]);
//             return response()->json([
//                 'status' => false,
//                 'message' => 'فشل التحقق من صحة البيانات.',
//                 'errors' => $e->errors(),
//             ], 422);
//         } catch (\Exception $e) {
//             Log::error('Error in confirmReservation: ' . $e->getMessage());
//             return response()->json([
//                 'status' => false,
//                 'message' => 'حدث خطأ أثناء تأكيد الحجز.',
//                 'error' => $e->getMessage(),
//             ], 500);
//         }
// }

}

    
