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
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon; // لاستخدام التواريخ والأوقات بسهولة

class ReservationController extends Controller
{
    /**
     * اختيار الصالة وتحديد تاريخ ووقت الحجز.
     * ينشئ أو يحدث حجزاً مؤقتاً (pending).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
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

            // التحقق من تضارب الحجوزات لنفس الصالة في نفس التاريخ والوقت
            $conflict = Reservation::where('hall_id', $hallId)
                ->where('reservation_date', $reservationDate)
                ->whereIn('status', ['confirmed', 'pending']) // تحقق من الحجوزات المؤكدة والمعلقة
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

            // البحث عن حجز معلق حالي للمستخدم أو إنشاء حجز جديد
            $reservation = Reservation::firstOrCreate(
                ['user_id' => auth()->id(), 'status' => 'pending'],
                [
                    'hall_id' => $hallId,
                    'reservation_date' => $reservationDate,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'event_id' => Hall::find($hallId)->event_type_id, // جلب event_type_id من الصالة
                    'status' => 'pending',
                ]
            );

            // إذا كان الحجز موجوداً بالفعل، قم بتحديثه
            if (!$reservation->wasRecentlyCreated) {
                $reservation->update([
                    'hall_id' => $hallId,
                    'reservation_date' => $reservationDate,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'event_id' => Hall::find($hallId)->event_type_id,
                ]);
                // حذف الخدمات القديمة إذا تم تغيير الصالة أو الوقت
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

    /**
     * إضافة خدمة إلى الحجز المؤقت (pending reservation).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addService(Request $request)
    {
        try {
            $request->validate([
                'service_variant_id' => 'required|exists:service_variants,id',
                'quantity' => 'required|integer|min:1',
            ]);

            $user = auth()->user();
            // البحث عن الحجز المعلق للمستخدم
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

            // إضافة أو تحديث عنصر الخدمة في الحجز
            $reservationServiceItem = ReservationService::updateOrCreate(
                [
                    'reservation_id' => $reservation->id,
                    'service_id' => $variant->service_id, // جلب service_id من الـ variant
                    'service_variant_id' => $request->service_variant_id,
                ],
                [
                    'quantity' => $request->quantity,
                    'unit_price' => $variant->price,
                ]
            );

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

    /**
     * إزالة خدمة من الحجز المؤقت.
     *
     * @param  int  $reservationServiceItemId
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * عرض ملخص الحجز المؤقت (السلة).
     *
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * تأكيد الحجز المؤقت وتحويله إلى حجز مؤكد.
     * يتضمن تعيين مشرف.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmReservation(Request $request)
    {
        try {
            $request->validate([
                'reservation_id' => 'required|exists:reservations,id',
                'home_address' => 'nullable|string|max:255', // مطلوب فقط إذا كانت الصالة 9999
                'discount_code' => 'nullable|string|exists:discount_codes,code', // إضافة كود الخصم
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

            // التحقق من أن الحجز يحتوي على صالة وخدمات على الأقل
            if (!$reservation->hall_id && $reservation->reservationServices->isEmpty()) {
                 return response()->json([
                    'status' => false,
                    'message' => 'لا يمكن تأكيد حجز فارغ. يرجى اختيار صالة أو خدمات أولاً.',
                ], 400);
            }

            // تطبيق كود الخصم (إذا تم تقديمه)
            $finalPrice = $this->calculateFinalPrice($reservation); // دالة لحساب السعر الإجمالي
            if ($request->filled('discount_code')) {
                $discountCode = \App\Models\DiscountCode::where('code', $request->discount_code)
                                                        ->where('is_active', true)
                                                        ->first();
                if ($discountCode) {
                    // تطبيق الخصم
                    $discountAmount = ($finalPrice * $discountCode->discount_percentage) / 100;
                    $finalPrice -= $discountAmount;
                    $reservation->discount_code_id = $discountCode->id; // حفظ كود الخصم في الحجز
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'كود الخصم غير صالح أو غير نشط.',
                    ], 400);
                }
            }
            $reservation->total_price = $finalPrice; // حفظ السعر النهائي بعد الخصم

            // البحث عن مشرف متاح (Coordinator)
            $availableCoordinator = Coordinator::whereDoesntHave('reservations', function($query) use ($reservation) {
                $query->where('reservation_date', $reservation->reservation_date)
                    ->where(function($q) use ($reservation) {
                        $q->whereBetween('start_time', [$reservation->start_time, $reservation->end_time])
                            ->orWhereBetween('end_time', [$reservation->start_time, $reservation->end_time])
                            ->orWhere(function($q2) use ($reservation) {
                                $q2->where('start_time', '<=', $reservation->start_time)
                                   ->where('end_time', '>=', $reservation->end_time);
                            });
                    });
            })
            ->withCount('reservations') // نحسب عدد الحجوزات لكل مشرف
            ->orderBy('reservations_count', 'asc') // ترتيب الأقل حجوزات
            ->first();

            if (!$availableCoordinator) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا يوجد مشرفين متاحين حالياً في هذا الوقت، الرجاء المحاولة لاحقاً أو تغيير وقت الحجز.',
                ], 400);
            }

            // تحديث الحجز إلى مؤكد
            $reservation->update([
                'status' => 'confirmed',
                'coordinator_id' => $availableCoordinator->id,
                'home_address' => ($reservation->hall_id == 9999 && $request->filled('home_address')) ? $request->home_address : null,
            ]);

            // تحميل العلاقات لعرضها في الاستجابة
            $reservation->load(['hall', 'event', 'reservationServices.service', 'reservationServices.serviceVariant', 'coordinator']);

            // إرسال إشعار للمستخدم (يمكن أن يكون إشعار قاعدة بيانات أو بريد إلكتروني)
            // Notification::send($user, new ReservationConfirmed($reservation)); // تحتاج إلى إنشاء فئة إشعار
// {$availableCoordinator->phone ?? 'غير متوفر'}
            return response()->json([
                'status' => true,
                'message' => "تم تأكيد الحجز بنجاح. سيتم إرسال رسالة تأكيد عند انتهاء الطلب. لأي استفسار، اتصل بالمنسق: {$availableCoordinator->name_ar} (هاتف:)",
                'reservation' => $reservation,
            ], 201); // 201 Created

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

    /**
     * تحديث تفاصيل الحجز (مثل كمية الخدمات).
     * متاح فقط للحجوزات المعلقة أو خلال 24 ساعة من الحجز.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $reservationId
     * @return \Illuminate\Http\JsonResponse
     */
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

            // السماح بالتعديل فقط للحجوزات المعلقة أو خلال 24 ساعة من تاريخ الإنشاء
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
                // يمكن إضافة حقول أخرى قابلة للتعديل هنا
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

            // إعادة حساب السعر الإجمالي بعد التعديل
            $reservation->total_price = $this->calculateFinalPrice($reservation);
            $reservation->save();

            $reservation->load(['hall', 'event', 'reservationServices.service', 'reservationServices.serviceVariant']);

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

    /**
     * إلغاء الحجز.
     * متاح فقط للحجوزات المعلقة أو خلال 24 ساعة من الحجز.
     *
     * @param  int  $reservationId
     * @return \Illuminate\Http\JsonResponse
     */
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

            // السماح بالإلغاء فقط للحجوزات المعلقة أو خلال 24 ساعة من تاريخ الإنشاء
            $canCancel = ($reservation->status === 'pending') ||
                         (Carbon::parse($reservation->created_at)->addHours(24)->isFuture() && $reservation->status === 'confirmed');

            if (!$canCancel) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا يمكن إلغاء هذا الحجز حالياً. يرجى التواصل مع الإدارة.',
                ], 403);
            }

            $reservation->update(['status' => 'cancelled']);

            // إرسال إشعار للمنسق وصاحب الصالة بأن الحجز قد تم إلغاؤه
            // Notification::send($reservation->coordinator, new ReservationCancelled($reservation));
            // Notification::send($reservation->hall->owner, new ReservationCancelled($reservation));

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

    /**
     * عرض جميع حجوزات المستخدم.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserReservations()
    {
        try {
            $user = auth()->user();
            $reservations = Reservation::with([
                'hall',
                'event',
                'reservationServices.service',
                'reservationServices.serviceVariant',
                'coordinator'
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

    /**
     * دالة مساعدة لحساب السعر الإجمالي للحجز.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return float
     */
    private function calculateFinalPrice(Reservation $reservation)
    {
        $totalPrice = 0;
        if ($reservation->hall) {
            $totalPrice += $reservation->hall->price;
        }

        foreach ($reservation->reservationServices as $item) {
            $totalPrice += $item->quantity * $item->unit_price;
        }

        return $totalPrice;
    }
}
