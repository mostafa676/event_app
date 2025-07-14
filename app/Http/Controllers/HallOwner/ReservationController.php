<?php

namespace App\Http\Controllers\HallOwner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hall;
use App\Models\Reservation; 
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // لاستخدام تسجيل الأخطاء

class ReservationController extends Controller
{
    /**
     * عرض جميع الحجوزات المتعلقة بالصالات التي يمتلكها المستخدم الحالي (مالك الصالة).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMyHallReservations(Request $request)
    {
        // 1. التحقق من أن المستخدم الحالي هو مالك صالة
        $user = Auth::user();
        if (!$user || $user->role !== 'hall_owner') {
            return response()->json(['message' => 'Unauthorized: You are not a hall owner.'], 403);
        }

        try {
            // 2. جلب معرّفات (IDs) جميع الصالات التي يمتلكها المستخدم الحالي
            // نفترض أن جدول 'halls' لديه عمود 'user_id' يربطه بجدول 'users'.
            $myHallIds = Hall::where('user_id', $user->id)->pluck('id');

            // 3. إذا لم يكن لدى مالك الصالة أي صالات مسجلة، فلا توجد حجوزات لعرضها
            if ($myHallIds->isEmpty()) {
                return response()->json(['message' => 'No halls found for this owner.'], 404);
            }

            // 4. جلب الحجوزات المرتبطة بهذه الصالات مع تحميل العلاقات الضرورية (Eager Loading)
          
            $reservations = Reservation::whereIn('hall_id', $myHallIds)
                                       ->with([
                                           'user', // المستخدم الذي قام بالحجز
                                           'hall', // الصالة المحجوزة
                                           'eventType', // نوع الحدث
                                           'reservationServices.service', // الخدمات المضافة للحجز وتفاصيل الخدمة
                                           'reservationServices.serviceVariant', // تفاصيل متغير الخدمة
                                           'reservationServices.song', // إذا كانت خدمة موسيقية (أغنية مختارة)
                                           'reservationServices.coordinator', // المنسق المربوط مباشرة بالخدمة (مثل مغني/مصور تم اختياره مسبقاً)
                                           'songRequests', // طلبات الأغاني المخصصة
                                           // 'coordinatorAssignment' // لا يمكننا تضمين هذه العلاقة هنا إلا إذا تم تعريفها في نموذج Reservation وربطها بـ CoordinatorAssignment
                                       ])
                                       ->latest() // ترتيب أحدث الحجوزات أولاً
                                       ->paginate(10); // تقسيم النتائج إلى صفحات (يمكن تعديل العدد)

            // 5. إذا لم يتم العثور على حجوزات لهذه الصالات
            if ($reservations->isEmpty()) {
                return response()->json(['message' => 'No reservations found for your halls.'], 404);
            }

            // 6. إرجاع الحجوزات بنجاح
            return response()->json([
                'message' => 'Reservations for your halls fetched successfully.',
                'data' => $reservations
            ], 200);

        } catch (\Exception $e) {
            // 7. تسجيل الخطأ في ملفات سجلات Laravel للمراجعة
            Log::error('Error fetching hall owner reservations: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'An error occurred while fetching reservations.', 'error' => $e->getMessage()], 500);
        }
    }
}