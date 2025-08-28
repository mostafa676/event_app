<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hall; 
use App\Models\Reservation; 
use App\Models\Favorite; 
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; 
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            // 1. عدد الصالات الكلية
            $totalHalls = Hall::count();

            // 2. عدد الحجوزات الملغاة
            $cancelledReservations = Reservation::where('status', 'cancelled')->count();

            // 3. عدد الحجوزات التي تم إنجازها
            $completedReservations = Reservation::where('status', 'completed')->count();

            // 4. الفعاليات القادمة (الحجوزات التي لم تحدث بعد)
            // نستخدم ->get() هنا لجلب تفاصيل الحجوزات، وليس فقط العدد.
            $upcomingEvents = Reservation::where('reservation_date', '>=', Carbon::today())
                                          ->orderBy('reservation_date', 'asc')
                                          ->orderBy('start_time', 'asc')
                                          ->with([
                                              'hall:id,name_ar,name_en,location_ar,location_en', // جلب حقول محددة من الصالة
                                              'user:id,name,email,phone', // جلب حقول محددة من المستخدم (العميل)
                                              'eventType:id,name_ar,name_en' // جلب حقول محددة من نوع الفعالية
                                          ])
                                          //->get();هنا اذا اردنا ارجاع تفاصيل الحجز القادم
                                          //ايضا تحت اذا اردنا تفاصيل
                                          
                                          ->count();


            // 5. الصالات الأكثر طلباً (Most Requested Halls)
            $mostRequestedHalls = Reservation::select('hall_id', DB::raw('count(*) as total_requests'))
                                            ->groupBy('hall_id')
                                            ->orderByDesc('total_requests')
                                            ->with('hall:id,name_ar,name_en')
                                            ->take(5)
                                            ->get();
                                            
                   
            // 6. الصالات المفضلة (Favorite Halls)
            $mostFavoritedHalls = Favorite::select('favoritable_id', DB::raw('count(*) as total_favorites'))
                                        ->where('favoritable_type', 'App\\Models\\Hall')
                                        ->groupBy('favoritable_id')
                                        ->orderByDesc('total_favorites')
                                        ->with('favoritable:id,name_ar,name_en')
                                        ->take(5)
                                        ->get();

            // 7. الإيرادات المالية للتطبيق خلال سنة (Annual Financial Revenue)
            $oneYearAgo = Carbon::now()->subYear();
            $annualRevenue = Reservation::whereIn('status', ['confirmed', 'completed'])
                                        ->where('reservation_date', '>=', $oneYearAgo)
                                        ->sum('total_price');

            return response()->json([
                'status' => true,
                'message' => 'تم جلب بيانات لوحة التحكم بنجاح.',
                'data' => [
                    'total_halls' => $totalHalls,
                    'cancelled_reservations' => $cancelledReservations,
                    'completed_reservations' => $completedReservations,
                    'upcoming_events' => $upcomingEvents, 
                    'most_requested_halls' => $mostRequestedHalls,
                    'most_favorited_halls' => $mostFavoritedHalls,
                    'annual_revenue' => $annualRevenue, 
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Dashboard data fetching error: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات لوحة التحكم.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllReservationsDetails(Request $request)
    {
        // تهيئة المتغير بقيمة افتراضية لمنع تحذير "متغير غير معين القيمة"
        $allReservations = collect(); // تم تصحيح هذا السطر: كان "collec" وأصبح "collect()"

        try { // تم إزالة كتلة try المتداخلة هنا
            $allReservationsRaw = Reservation::orderBy('reservation_date', 'asc')
                                          ->orderBy('start_time', 'asc')
                                          ->with([
                                              'hall:id,name_ar,name_en,user_id',
                                              'user:id,name',
                                          ])
                                          ->get();

            // تنسيق جميع الحجوزات لعرض التفاصيل المطلوبة فقط: صاحب الحجز، الصالة المحجوزة، وتاريخ الحجز
            $allReservations = $allReservationsRaw->map(function ($reservation) {
                return [
                    'reservation_id' => $reservation->id,
                    'reservation_date' => $reservation->reservation_date, // تاريخ الحجز
                    'reservation_owner_name' => $reservation->user->name ?? null, // اسم صاحب الحجز (العميل)
                    'hall_name_ar' => $reservation->hall->name_ar ?? null, // اسم الصالة بالعربي
                    'hall_name_en' => $reservation->hall->name_en ?? null, // اسم الصالة بالإنجليزي
                    'status' => $reservation->status, // إضافة حالة الحجز
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'تم جلب تفاصيل جميع الحجوزات بنجاح.',
                'all_reservations_details' => $allReservations,
            ], 200);

        } catch (\Exception $e) { // تم إغلاق كتلة try-catch بشكل صحيح هنا
            Log::error('Error fetching all reservation details: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب تفاصيل جميع الحجوزات.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getEventsByDateRange(Request $request)
    {
        try {
            // التحقق من صحة المدخلات: start_date و end_date
            $validatedData = $request->validate([
                'start_date' => 'required|date_format:Y-m-d',
                'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            ]);

            $startDate = Carbon::parse($validatedData['start_date'])->startOfDay();
            $endDate = Carbon::parse($validatedData['end_date'])->endOfDay();

            // جلب الحجوزات التي تقع تواريخها بين start_date و end_date
            $filteredReservationsRaw = Reservation::whereBetween('reservation_date', [$startDate, $endDate])
                                                ->orderBy('reservation_date', 'asc')
                                                ->orderBy('start_time', 'asc')
                                                ->with([
                                                    'hall:id,name_ar,name_en,user_id', // جلب حقول الصالة
                                                    'user:id,name', // جلب حقول العميل
                                                    'eventType:id,name_ar,name_en' // جلب حقول نوع الفعالية
                                                ])
                                                ->get();

            // تنسيق البيانات لعرض التفاصيل المطلوبة
            $filteredEvents = $filteredReservationsRaw->map(function ($reservation) {
                return [
                    'reservation_id' => $reservation->id,
                    'reservation_date' => $reservation->reservation_date,
                    'status' => $reservation->status,
                    'reservation_owner_name' => $reservation->user->name ?? null,
                    'hall_name_ar' => $reservation->hall->name_ar ?? null,
                    'hall_name_en' => $reservation->hall->name_en ?? null,
                    'event_type_name_ar' => $reservation->eventType->name_ar ?? null,
                    'event_type_name_en' => $reservation->eventType->name_en ?? null,
                ];
            });

            if ($filteredEvents->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا توجد فعاليات في النطاق الزمني المحدد.',
                    'events' => [],
                ], 200);
            }

            return response()->json([
                'status' => true,
                'message' => 'تم جلب الفعاليات بنجاح للنطاق الزمني المحدد.',
                'events' => $filteredEvents,
            ], 200);

        } catch (ValidationException $e) {
            Log::error('Validation error fetching events by date range: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات المدخلة.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error fetching events by date range: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب الفعاليات للنطاق الزمني المحدد.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}