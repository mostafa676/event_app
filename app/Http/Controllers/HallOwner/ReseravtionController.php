<?php
namespace App\Http\Controllers\HallOwner;
use App\Http\Controllers\Controller;
use App\Models\Coordinator;
use App\Models\CoordinatorAssignment;
use App\Models\CoordinatorType;
use App\Models\Hall;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;
USE App\Helpers\NotificationHelper;

class ReseravtionController extends Controller
{

// nnn 1
public function assignCoordinatorsToReservation($reservationId)
{
    $reservation = Reservation::with('reservationServices.service')->findOrFail($reservationId);
    $assignments = [];
    $existingServiceIds = [];
    $notifications = []; 

    foreach ($reservation->reservationServices as $reservationService) {
        $service = $reservationService->service;

        // تجنب تكرار الخدمة
        if (in_array($service->id, $existingServiceIds)) {
            continue;
        }
        $existingServiceIds[] = $service->id;

        // إذا المستخدم حدد منسق بنفسه لهذه الخدمة
        if (!empty($reservationService->coordinator_id)) {
            $coordinator = Coordinator::find($reservationService->coordinator_id);
        } else {
            // نجيب نوع المنسق
            $coordinatorType = CoordinatorType::where('name_en', $service->name_en)->first();

            if (!$coordinatorType) {
                continue;
            }

            // نجيب المنسق اللي عنده أقل عدد مهام
            $coordinator = Coordinator::where('coordinator_type_id', $coordinatorType->id)
                ->withCount(['assignments as tasks_count' => function ($q) {
                    $q->whereIn('status', ['pending', 'accepted']); // المهام النشطة فقط
                }])
                ->orderBy('tasks_count', 'asc')
                ->first();
        }

        if ($coordinator) {
            $assignment = CoordinatorAssignment::create([
                'reservation_id' => $reservation->id,
                'coordinator_id' => $coordinator->id,
                'reservation_service_id' => $reservationService->id,
                'service_id' => $service->id,
                'assigned_by' => Auth::id(),
                'status' => 'pending',
            ]);

            // إشعار للمنسق
            $notifications = NotificationHelper::sendFCM(
                $coordinator->user,
                'task_assigned',
                'تم تعيين مهمة جديدة',
                'تم تعيينك لخدمة: ' . $service->name_ar,
                data: [
                    'assignment_id' => $assignment->id,
                    'reservation_id' => $reservation->id,
                    'notifiable_id' => $assignment->id,
                    'notifiable_type' => "task_assigned"
                ]
            );

            $assignments[] = [
                'assignment_id' => $assignment->id,
                'service' => $service->name_en,
                'coordinator' => $coordinator?->user?->name ?? 'غير محدد',
                'notification' => $notification,
                'status' => $assignment->status
            ];
        }
        $reservation->status = 'working_in';
        $reservation->save();
    }

    return response()->json([
        'status' => true,
        'message' => 'تم توزيع المهام على المنسقين بنجاح.',
        'assignments' => $assignments,
        'notifications' => $notifications
    ]);
}

public function getReservationsForHallOwner()
    {
        $userId = Auth::id(); // صاحب الصالة المسجل دخول
        $reservations = Reservation::whereIn('hall_id', function ($query) use ($userId) {
                $query->select('id')
                      ->from('halls')
                      ->where('user_id', $userId);
            })
            ->with(['hall', 'user']) // لو أردت معلومات عن المستخدم/الصالة
            ->get();

        if ($reservations->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'لا توجد حجوزات مرتبطة بصالاتك حالياً.',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم جلب الحجوزات المرتبطة بصالاتك.',
            'reservations' => $reservations,
        ]);
}

public function show($id)
{
    try {
        $reservation = Reservation::with([
            'user',
            'hall',
            'services.service'
        ])->find($id);

        if (!$reservation) {
            return response()->json([
                'status' => false,
                'message' => 'الحجز غير موجود.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم جلب تفاصيل الحجز بنجاح.',
            'reservation' => [
                'id' => $reservation->id,
                'reservation_date' => $reservation->reservation_date,
                'start_time' => $reservation->start_time,
                'end_time' => $reservation->end_time,
                'status' => $reservation->status,
                'total_price' => $reservation->total_price,
                'user' => [
                    'name' => $reservation->user->name,
                    'email' => $reservation->user->email,
                    'phone' => $reservation->user->phone,
                ],
                'hall' => [
                    'name_ar' => $reservation->hall->name_ar,
                    'name_en' => $reservation->hall->name_en,
                    'location_ar' => $reservation->hall->location_ar,
                    'location_en' => $reservation->hall->location_en,
                    'price' => $reservation->hall->price,
                ],
                'services' => $reservation->services->map(function ($reservationService) {
                    return [
                        'id' => $reservationService->service->id ?? null,
                        'name_ar' => $reservationService->service->name_ar ,
                        'name_en' => $reservationService->service->name_en ,
                        'quantity' => $reservationService->quantity,
                        'unit_price' => $reservationService->unit_price,
                    ];
                }),
            ]
        ], 200);

    } catch (\Exception $e) {
        \Log::error('Error fetching reservation details: ' . $e->getMessage());

        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء جلب تفاصيل الحجز.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


public function getOrganizedIncompleteReservations()
{
    try {
        $reservations = Reservation::where('status', 'working_in')
            ->with(['tasks'])
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'تم جلب الحجوزات غير المكتملة بنجاح.',
            'reservations' => $reservations
        ], 200);
    } catch (\Exception $e) {
        \Log::error('Error fetching incomplete reservations: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء جلب الحجوزات.',
            'error' => $e->getMessage()
        ], 500);
    }
}
public function getTasksByReservationAndStatus($reservationId, $status)
{
    // الحالات المسموح بها
    $allowedStatuses = ['working_on', 'accepted', 'rejected', 'done'];
    if (!in_array($status, $allowedStatuses)) {
        return response()->json([
            'status' => false,
            'message' => 'الحالة غير صالحة. يُسمح فقط: working_on, accepted, rejected, done.'
        ], 422);
    }

    try {
        $query = CoordinatorAssignment::with(['reservation', 'reservation.user', 'reservation.hall'])
            ->where('reservation_id', $reservationId);

        // إذا الحالة "done" رجّع accepted + rejected
        if ($status === 'done') {
            $query->whereIn('status', ['accepted', 'rejected']);
        } else {
            $query->where('status', $status);
        }

        $tasks = $query->get();

        if ($tasks->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'لا توجد مهام بهذه الحالة ضمن هذا الحجز.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم جلب المهام بنجاح.',
            'tasks' => $tasks
        ], 200);
    } catch (\Exception $e) {
        \Log::error('Error fetching tasks: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء جلب المهام.',
            'error' => $e->getMessage()
        ], 500);
    }
}


public function showTask($id)
{
    try {
        $task = CoordinatorAssignment::with([
            'reservation',
            'reservation.user',
            'reservation.hall',
            'reservation.services'
        ])->find($id);

        if (!$task) {
            return response()->json([
                'status' => false,
                'message' => 'المهمة غير موجودة.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم جلب تفاصيل المهمة بنجاح.',
            'task' => $task
        ], 200);
    } catch (\Exception $e) {
        \Log::error('Error fetching task details: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء جلب تفاصيل المهمة.',
            'error' => $e->getMessage()
        ], 500);
    }
}

// nnn 3
public function confirmReservationinhallowner($reservationId)
{
    $reservation = Reservation::findOrFail($reservationId);

    // تأكيد الحجز
    $reservation->status = 'completed';
    $reservation->save();

    // جلب المستخدم
    $user = $reservation->user;

    // رسالة الإشعار
    $title = 'تم تأكيد الحجز';
    $body  = "تم تأكيد حجزك رقم #{$reservation->id} بنجاح.";

    // إرسال الإشعار
    $notification = NotificationHelper::sendFCM(
        $user,
        'reservation_confirmed',
        $title,
        $body,
        [
            'reservation_id' => $reservation->id,
            'notifiable_id' => $reservation->id,
            'notifiable_type' => Reservation::class, 
        ]
    );

    return response()->json([
        'status' => true,
        'message' => 'تم تأكيد الحجز وإرسال الإشعار بنجاح.',
        'reservation' => $reservation,
        'notification' => $notification, // الآن data ستكون array مقروء
    ], 200);
}

}

// public function assignCoordinatorsToReservation($reservationId)
// {
//     $reservation = Reservation::with('services.service')->findOrFail($reservationId);
//     $assignments = [];
// $existingServiceIds=[] ;
//     foreach ($reservation->services as $reservationService) {
//         $service = $reservationService->service;
//        if (in_array($service->id, $existingServiceIds)) {
//     continue;
// }
// $existingServiceIds[] = $service->id;
//         //(مثلاً DJ للطرب، Chef للطعام)
//         $coordinatorType = CoordinatorType::where('name_en', $service->name_en)->first();

//         if ($coordinatorType) {
//             $coordinator = Coordinator::where('coordinator_type_id', $coordinatorType->id)->first();

//             if ($coordinator) {
//                 $assignment = CoordinatorAssignment::create([
//     'reservation_id' => $reservation->id,
//     'coordinator_id' => $coordinator->id,
//     'reservation_service_id' => $reservationService->id,
//     'service_id' => $service->id, // أضف هذا السطر
//     'assigned_by' => Auth::id(),
//     'status' => 'pending',
// ]);

                  
//                 $assignments[] = [
//                     'assignment_id' => $assignment->id,
//                     'service' => $service->name_en,
//                     'coordinator' => $coordinator?->user?->name ?? 'يا لهوووي  ',
//                     'status' => $assignment->status,
//                 ];
//             }
//         }
//     }

//     return response()->json([
//         'status' => true,
//         'message' => 'تم توزيع المهام على المنسقين بنجاح.',
//         'assignments' => $assignments,
//     ]);
// }
