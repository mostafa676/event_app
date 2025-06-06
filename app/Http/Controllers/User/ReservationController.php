<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Hall;
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

class ReservationController extends Controller
{
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
            $startTime = $request->start_time;
            $endTime = $request->end_time;
            $conflict = Reservation::where('hall_id', $hallId)
                ->where('reservation_date', $reservationDate)
                ->whereIn('status', ['confirmed', 'pending'])
                 ->where(function ($query) use ($startTime, $endTime) {
                    $query->where(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<', $endTime)
                            ->where('end_time', '>', $startTime);
                    });
                })
                ->exists();

            if ($conflict) {
                return response()->json([
                    'status' => false,
                    'message' => 'عذراً، هذه الفترة الزمنية محجوزة بالفعل للصالة المطلوبة أو هناك حجز معلق.',
                ], 400);
            }
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
            Log::error('Validation error in selectHall: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in selectHall: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء اختيار الصالة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function addService(Request $request)
    {
        try {
            $request->validate([
                'service_variant_id' => 'required|exists:service_variants,id',
                'quantity' => 'required|integer|min:1|defult=1',
                'coordinator_id' => 'nullable|exists:coordinators,id', // يمكن أن يكون DJ أو مصور
                'song_id' => 'nullable|exists:songs,id', // للأغاني الموجودة
                'custom_song_title' => 'nullable|string|max:255', // للأغاني المخصصة
                'custom_song_artist' => 'nullable|string|max:255', // للأغاني المخصصة
            ]);

            $user = auth()->user();
            $reservation = Reservation::where('user_id', $user->id)
                                    ->where('status', 'pending')
                                    ->first();

            if (!$reservation) {
                return response()->json([
                    'status' => false,
                    'message' => 'يجب اختيار صالة أولاً لإنشاء حجز مؤقت.',
                ], 400);
            }

            $variant = ServiceVariant::findOrFail($request->service_variant_id);
            $service = $variant->service; 
            $additionalData = [];

            // مثال: إذا كانت هذه الخدمة هي خدمة "موسيقى" (DJ)
            // ستحتاج إلى معرفة service_id الخاص بخدمة الموسيقى
            // يمكنك تعريفه كثابت أو جلبه من قاعدة البيانات
            // افترض أن service_id لخدمة الموسيقى هو 100
            if ($service->id == 1) { // استبدل 100 بالـ service_id الفعلي لخدمة الموسيقى
                if ($request->has('song_id')) {
                    $additionalData['song_id'] = $request->song_id;
                } elseif ($request->has('custom_song_title') && $request->has('custom_song_artist')) {
                    $additionalData['custom_song_title'] = $request->custom_song_title;
                    $additionalData['custom_song_artist'] = $request->custom_song_artist;
                }
                if ($request->has('coordinator_id')) {
                    $additionalData['coordinator_id'] = $request->coordinator_id;
                } else {
                     return response()->json([
                        'status' => false,
                        'message' => 'يرجى اختيار منسق (DJ) لخدمة الموسيقى.',
                    ], 400);
                }
            }
            if ($service->id == 200) { 
                if ($request->has('coordinator_id')) {
                    $additionalData['coordinator_id'] = $request->coordinator_id;
                } else {
                     return response()->json([
                        'status' => false,
                        'message' => 'يرجى اختيار منسق (مصور) لخدمة التصوير.',
                    ], 400);
                }
            }
            $baseData = [
                'reservation_id' => $reservation->id,
                'service_id' => $variant->service_id,
                'service_variant_id' => $request->service_variant_id,
            ];
            $dataToCreateOrUpdate = array_merge($baseData, [
                'quantity' => $request->quantity,
                'unit_price' => $variant->price,
            ], $additionalData); 
            $reservationServiceItem = ReservationService::updateOrCreate(
                $baseData, 
                $dataToCreateOrUpdate
            );

            $reservation->total_price = $reservation->reservationServices()->sum(DB::raw('quantity * unit_price'));
            $reservation->save();

            return response()->json([
                'status' => true,
                'message' => 'تمت إضافة الخدمة إلى الحجز المؤقت بنجاح.',
                'item' => $reservationServiceItem,
            ], 200);

        } catch (ValidationException $e) {
            Log::error('Validation error in addService: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in addService: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إضافة الخدمة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function removeService($reservationServiceItemId)
    {
        try {
            $user = auth()->user();
            $reservation = Reservation::where('user_id', $user->id)
                                    ->where('status', 'pending')
                                    ->first();

            if (!$reservation) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا يوجد حجز مؤقت لإزالة الخدمات منه.',
                ], 404);
            }

            $item = ReservationService::where('id', $reservationServiceItemId)
                                    ->where('reservation_id', $reservation->id)
                                    ->first();

            if (!$item) {
                return response()->json([
                    'status' => false,
                    'message' => 'عنصر الخدمة غير موجود في الحجز المؤقت.',
                ], 404);
            }

            $item->delete();

            return response()->json([
                'status' => true,
                'message' => 'تم حذف الخدمة من الحجز المؤقت بنجاح.',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in removeService: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء حذف الخدمة.',
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
                'event',
                'reservationServices.service',
                'reservationServices.serviceVariant'
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

    public function confirmReservation(Request $request)
    {
        try {
            $request->validate([
                'reservation_id' => 'required|exists:reservation,id',
                'home_address' => 'nullable|string|max:255', // مطلوب فقط إذا كانت الصالة 9999
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
            if (!$reservation->hall_id && $reservation->reservationServices->isEmpty()) {
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
                    // تطبيق الخصم
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
            $reservation->update([
                'status' => 'confirmed',
                'home_address' => ($reservation->hall_id == 9999 && $request->filled('home_address')) ? $request->home_address : null,
            ]);
            $reservation->load(['hall', 'eventType', 'reservationServices.service', 'reservationServices.serviceVariant']);
            return response()->json([
                'status' => true,
                'message' => "تم تأكيد الحجز بنجاح. سيتم إرسال رسالة تأكيد عند انتهاء الطلب",
                'reservation' => $reservation,
            ], 201); 

        } catch (ValidationException $e) {
            Log::error('Validation error in confirmReservation: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in confirmReservation: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تأكيد الحجز.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

 // تحديث تفاصيل الحجز (مثل كمية الخدمات).
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

// إلغاء الحجز.
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

//عرض جميع حجوزات المستخدم.

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

   // دالة مساعدة لحساب السعر الإجمالي للحجز
    private function calculateFinalPrice(Reservation $reservation)
    {
        $totalPrice = 0;
        if ($reservation->hall) {
            $totalPrice += $reservation->hall->price;
        }
        if ($reservation->reservationServices) {
            foreach ($reservation->reservationServices as $item) {
                $itemTotalPrice = $item->quantity * $item->unit_price;
                $totalPrice += $itemTotalPrice;
            }
        }
        return $totalPrice;
    }
}
