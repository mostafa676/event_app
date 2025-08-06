<?php
namespace App\Http\Controllers\HallOwner;
use App\Http\Controllers\Controller;
use App\Models\Coordinator;
use App\Models\CoordinatorAssignment;
use App\Models\CoordinatorType;
use App\Models\Hall;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;

class ReseravtionController extends Controller
{
public function assignCoordinatorsToReservation($reservationId)
{
    $reservation = Reservation::with('services.service')->findOrFail($reservationId);
    $assignments = [];
$existingServiceIds=[] ;
    foreach ($reservation->services as $reservationService) {
        $service = $reservationService->service;
       if (in_array($service->id, $existingServiceIds)) {
    continue;
}
$existingServiceIds[] = $service->id;
        //(مثلاً DJ للطرب، Chef للطعام)
        $coordinatorType = CoordinatorType::where('name_en', $service->name_en)->first();

        if ($coordinatorType) {
            $coordinator = Coordinator::where('coordinator_type_id', $coordinatorType->id)->first();

            if ($coordinator) {
                $assignment = CoordinatorAssignment::create([
                     'reservation_id' => $reservation->id,
                    'coordinator_id' => $coordinator->id,
                    'reservation_service_id' => $reservationService->id,
                    'assigned_by' => Auth::id(),
                    'status' => 'pending',
                ]);
                  
                $assignments[] = [
                    'assignment_id' => $assignment->id,
                    'service' => $service->name_en,
                    'coordinator' => $coordinator?->user?->name ?? 'يا لهوووي  ',
                    'status' => $assignment->status,
                ];
            }
        }
    }

    return response()->json([
        'status' => true,
        'message' => 'تم توزيع المهام على المنسقين بنجاح.',
        'assignments' => $assignments,
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
        $reservation = Reservation::with(['user', 'hall', 'services'])->find($id);

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
                'services' => $reservation->services->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'price' => $service->price,
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
        $reservations = Reservation::with(['tasks'])
            ->whereHas('tasks', function ($query) {
                $query->where('status', '!=', 'completed');
            })
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
    $allowedStatuses = ['pending', 'accepted', 'rejected'];
    if (!in_array($status, $allowedStatuses)) {
        return response()->json([
            'status' => false,
            'message' => 'الحالة غير صالحة. يُسمح فقط: pending, accepted, rejected.'
        ], 422);
    }

    try {
        $tasks = CoordinatorAssignment::with(['reservation', 'reservation.user', 'reservation.hall'])
            ->where('status', $status)
            ->where('reservation_id', $reservationId)
            ->get();

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



}