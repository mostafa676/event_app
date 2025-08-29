<?php
namespace App\Http\Controllers\coordinator;
use App\Http\Controllers\Controller;
use App\Models\Coordinator;
use App\Models\CoordinatorAssignment;
use App\Models\CoordinatorType;
use App\Helpers\NotificationHelper;

use Illuminate\Support\Facades\Auth;

class CoordinatorController extends Controller
{
public function myAssignments()
{
    $userId = Auth::id();
    $coordinator = Coordinator::where('user_id', $userId)->first();

    if (!$coordinator) {
        return response()->json([
            'status' => false,
            'message' => 'لم العثور على المنسق المرتبط بهذا الحساب.',
        ], 200);
    }
    $assignments = $coordinator->assignments()->with(['service', 'reservation'])->get();
    return response()->json([
        'status' => true,
        'message' => 'تم جلب المهام الخاصة بك بنجاح.',
        'data' => $assignments
    ]);
}

public function pendingAssignments()
{
    $userId = Auth::id();
    $coordinator = Coordinator::where('user_id', $userId)->first();

    if (!$coordinator) {
        return response()->json([
            'status' => false,
            'message' => 'لم يتم العثور على المنسق المرتبط بهذا الحساب.',
        ], 200);
    }

    $assignments = $coordinator->assignments()
        ->working_on()
        ->with(['service', 'reservation'])
        ->get()
        ->map(function ($assignment) {
            $reservation = $assignment->reservation;
            $hall = $reservation->hall;

            return [
                'assignment_id' => $assignment->id,
                'service' => $assignment->service->name_en ?? 'غير معروف',
                'status' => $assignment->status,
            'description' => $hall
                ? "حفلة في الصالة {$hall->name_ar} بتاريخ {$reservation->reservation_date} من {$reservation->start_time} إلى {$reservation->end_time}"
                : "", // إذا لم يوجد hall اجعلها فارغة
        ];
        });;

    return response()->json([
        'status' => true,
        'message' => 'تم جلب المهام المعلقة بنجاح.',
        'data' => $assignments,

    ]);
}

// public function acceptAssignment($assignmentId)
// {

//     $assignment = CoordinatorAssignment::where('id', $assignmentId)
//         ->where('coordinator_id', Auth::user()->coordinatorProfile->id)
//         ->firstOrFail();

//     $assignment->status = 'accepted';
//     $assignment->save();

//     return response()->json([
//         'status' => true,
//         'message' => 'تم قبول المهمة بنجاح.',
//         'data' => $assignment
//     ]);
// }

// public function rejectAssignment($assignmentId)
// {
//     $assignment = CoordinatorAssignment::where('id', $assignmentId)
//         ->where('coordinator_id', Auth::user()->coordinatorProfile->id)
//         ->firstOrFail();

//     $assignment->status = 'rejected';
//     $assignment->save();

//     return response()->json([
//         'status' => true,
//         'message' => 'تم رفض المهمة بنجاح.',
//         'data' => $assignment
//     ]);
// }






// nnn 4
public function acceptAssignment($assignmentId){
    $coordinator = Coordinator::where('user_id', Auth::id())->first();

    if (!$coordinator) {
        return response()->json([
            'status' => false,
            'message' => 'لم يتم العثور على بيانات المنسق.'
        ], 404);
    }

    $assignment = CoordinatorAssignment::where('id', $assignmentId)
        ->where('coordinator_id', $coordinator->id)
        ->firstOrFail();

    $assignment->status = 'accepted';
    $assignment->save();

// إشعار لصاحب الصالة
    if ($assignment->reservation && $assignment->reservation->hall && $assignment->reservation->hall->user) {
        NotificationHelper::sendFCM(
            $assignment->reservation->hall->user,
            'task_accepted',
            'تم قبول المهمة',
            'قبل المنسق ' . Auth::user()->name . ' المهمة الخاصة بحجزك.',
            [
                'assignment_id' => $assignment->id,
                'reservation_id' => $assignment->reservation->id,
                'notifiable_id' => $assignment->id,
                'notifiable_type' => CoordinatorAssignment::class
            ]
        );
    }

    return response()->json([
        'status' => true,
        'message' => 'تم قبول المهمة بنجاح.',
        'data' => $assignment
    ]);
}

public function rejectAssignment($assignmentId)
{
    $coordinator = Coordinator::where('user_id', Auth::id())->first();
    if (!$coordinator) {
        return response()->json([
            'status' => false,
            'message' => 'لم يتم العثور على بيانات المنسق.'
        ], 404);
    }

    $assignment = CoordinatorAssignment::where('id', $assignmentId)
        ->where('coordinator_id', $coordinator->id)
        ->first();

    if (!$assignment) {
        return response()->json([
            'status' => false,
            'message' => 'المهمة غير موجودة أو غير مخصصة لك.'
        ], 404);
    }

    $assignment->status = 'rejected';
    $assignment->save();

    // محاولة إعادة التعيين لمنسق آخر
    $this->reassignTask($assignment);

    return response()->json([
        'status' => true,
        'message' => 'تم رفض المهمة وإعادة التعيين إذا كان متاح.',
        'assignment' => $assignment
    ]);
}


 
 public function nonPendingAssignments()
{
    $userId = Auth::id();
    $coordinator = Coordinator::where('user_id', $userId)->first();

    if (!$coordinator) {
        return response()->json([
            'status' => false,
            'message' => 'لم يتم العثور على المنسق.',
        ], 404);
    }

    $assignments = $coordinator->assignments()
        ->where('status', '!=', 'working_on')
        ->with(['service', 'reservation.hall'])
        ->get()
        ->map(function ($assignment) {
            $reservation = $assignment->reservation;
            $hallName = $reservation && $reservation->hall ? $reservation->hall->name_ar : "";

return [
                'assignment_id' => $assignment->id,
                'service' => $assignment->service->name_en ?? 'غير معروف',
                'status' => $assignment->status,
                'description' => $reservation
                    ? "حفلة في الصالة {$hallName} بتاريخ {$reservation->reservation_date} من {$reservation->start_time} إلى {$reservation->end_time}"
                    : "",
            ];
        });

    return response()->json([
        'status' => true,
        'message' => 'تم جلب المهام المنفذة أو المرفوضة.',
        'data' => $assignments,
    ]);
}

protected function reassignTask(CoordinatorAssignment $assignment)
{
    $service = $assignment->service;

    // نبحث عن منسق آخر لنفس نوع الخدمة
    $coordinatorType = CoordinatorType::where('name_en', $service->name_en)->first();
    if (!$coordinatorType) return; // إذا لم يوجد نوع منسق، نتوقف

    $newCoordinator = Coordinator::where('coordinator_type_id', $coordinatorType->id)
        ->where('id', '!=', $assignment->coordinator_id) // استبعاد المنسق السابق
        ->withCount(['assignments as tasks_count' => function ($q) {
            $q->whereIn('status', ['working_on', 'accepted']);
        }])
        ->orderBy('tasks_count', 'asc')
        ->first();

    if ($newCoordinator) {
        // إنشاء مهمة جديدة للمنسق الجديد
        $newAssignment = CoordinatorAssignment::create([
            'reservation_id' => $assignment->reservation_id,
            'coordinator_id' => $newCoordinator->id,
            'service_id' => $service->id,
            'assigned_by' => Auth::id(),
            'status' => 'working_on',
        ]);

        // إرسال إشعار للمنسق الجديد
        NotificationHelper::sendFCM(
            $newCoordinator->user,
            'task_assigned',
            'تم تعيين مهمة جديدة',
            'تم تعيينك لخدمة: ' . $service->name_ar,
            [
                'assignment_id' => $newAssignment->id,
                'reservation_id' => $assignment->reservation_id,
                'notifiable_id' => $newAssignment->id,
                'notifiable_type' => 'task_assigned'
            ]
        );
    } else {
        // إذا لم يوجد منسق متاح، يمكن إرسال إشعار للمالك أو تسجيل تنبيه
        $reservationOwner = $assignment->reservation->user;
        NotificationHelper::sendFCM(
            $reservationOwner,
            'service_unassigned',
            'لا يوجد منسق متاح',
            'الخدمة ' . $service->name_ar . ' لم يتم تعيين منسق لها بعد رفض المنسق السابق.',
            [
                'reservation_id' => $assignment->reservation_id,
            ]
        );
    }
}


}


