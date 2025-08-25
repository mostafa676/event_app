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
        ->pending()
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
public function acceptAssignment($assignmentId)
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
    $coordinator =Coordinator::where('user_id', Auth::id())->first();

    if (!$coordinator) {
        return response()->json([
            'status' => false,
            'message' => 'لم يتم العثور على بيانات المنسق.'
        ], 404);
    }

    $assignment = CoordinatorAssignment::where('id', $assignmentId)
        ->where('coordinator_id', $coordinator->id)
        ->firstOrFail();

    $assignment->status = 'rejected';
    $assignment->save();

    // إشعار لصاحب الصالة
    if ($assignment->reservation && $assignment->reservation->hall && $assignment->reservation->hall->user) {
        NotificationHelper::sendFCM(
            $assignment->reservation->hall->user,
            'task_rejected',
            'تم رفض المهمة',
            'رفض المنسق ' . Auth::user()->name . ' المهمة الخاصة بحجزك.',
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
        'message' => 'تم رفض المهمة بنجاح.',
        'data' => $assignment
    ]);
}

 

// public function nonPendingAssignments()
// {
//     $userId = Auth::id();
//     $coordinator = Coordinator::where('user_id', $userId)->first();

//     if (!$coordinator) {
//         return response()->json([
//             'status' => false,
//             'message' => 'لم يتم العثور على المنسق.',
//         ], 404);
//     }

//             // التأكد من وجود reservation و hall


//     $assignments = $coordinator->assignments()
//         ->where('status', '!=', 'pending')
//         ->with(['service', 'reservation'])
//         ->get();
//     $reservation = $assignment->reservation;
//         $hallName = $reservation && $reservation->hall ? $reservation->hall->name_ar : "";

//     return response()->json([
//         'status' => true,
//         'message' => 'تم جلب المهام المنفذة أو المرفوضة.',
//         'data' => $assignments,
//         'description' => $reservation
//                 ? "حفلة في الصالة {$hallName} بتاريخ {$reservation->reservation_date} من {$reservation->start_time} إلى {$reservation->end_time}"
//                 : "", // إذا لم يوجد reservation اجعلها فارغة
//         ]);
// }
// 

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
        ->where('status', '!=', 'pending')
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


}



/*
namespace App\Http\Controllers\coordinator;
use App\Http\Controllers\Controller;
use App\Models\Coordinator;
use App\Models\CoordinatorAssignment;
use App\Models\CoordinatorType;
use App\Helpers\NotificationHelper;

use Illuminate\Support\Facades\Auth;

class CoordinatorController extends Controller
{

public function pendingAssignments()
{
    $userId = Auth::id();
    $coordinator = Coordinator::where('user_id', $userId)->first();

    if (!$coordinator) {
        return response()->json([
            'status' => false,
            'message' => 'لم يتم العثور على المنسق المرتبط بهذا الحساب.',
        ], 404);
    }

    $assignments = $coordinator->assignments()
        ->pending()
        ->with(['service', 'reservation'])
        ->get()
        ->map(function ($assignment) {
            $reservation = $assignment->reservation;
            $hall = $reservation->hall;

            return [
                'assignment_id' => $assignment->id,
                'service' => $assignment->service->name_en ?? 'غير معروف',
                'status' => $assignment->status,
                'description' => "حفلة في الصالة {$hall->name_ar} بتاريخ {$reservation->reservation_date} من {$reservation->start_time} إلى {$reservation->end_time}",
            ];
        });;

    return response()->json([
        'status' => true,
        'message' => 'تم جلب المهام المعلقة بنجاح.',
        'data' => $assignments,
    ]);
}

// nnn 4
public function acceptAssignment($assignmentId)
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
        ->firstOrFail();

    $assignment->status = 'accepted';
    $assignment->save();

    $notification = null; // 🔹 تعريف مبدئي

    // إشعار لصاحب الصالة
    if ($assignment->reservation && $assignment->reservation->hall && $assignment->reservation->hall->owner) {
    $notification = NotificationHelper::sendFCM(
        $assignment->reservation->hall->owner,
        'task_accepted',
        'تم قبول المهمة',
        'قبل المنسق ' . Auth::user()->name . ' المهمة الخاصة بحجزك.',
        [
            'assignment_id' => $assignment->id,
            'reservation_id' => $assignment->reservation->id,
            'notifiable_id' => $assignment->id,
            'notifiable_type' => " إشعار لصاحب الصالة قبول مهمة "
        ]
    );
}

    return response()->json([
        'status' => true,
        'message' => 'تم قبول المهمة بنجاح.',
        'data' => $assignment,
        'notification' => $notification
    ]);

}

public function rejectAssignment($assignmentId)
{
    $coordinator =Coordinator::where('user_id', Auth::id())->first();

    if (!$coordinator) {
        return response()->json([
            'status' => false,
            'message' => 'لم يتم العثور على بيانات المنسق.'
        ], 404);
    }

    $assignment = CoordinatorAssignment::where('id', $assignmentId)
        ->where('coordinator_id', $coordinator->id)
        ->firstOrFail();

    $assignment->status = 'rejected';
    $assignment->save();
$notification = null; // 🔹 تعريف مبدئي
    // إشعار لصاحب الصالة
    if ($assignment->reservation && $assignment->reservation->hall && $assignment->reservation->hall->owner) {
    $notification = NotificationHelper::sendFCM(
        $assignment->reservation->hall->owner,
        'task_rejected',
        'تم رفض المهمة',
        'رفض المنسق ' . Auth::user()->name . ' المهمة الخاصة بحجزك.',
        [
            'assignment_id' => $assignment->id,
            'reservation_id' => $assignment->reservation->id,
            'notifiable_id' => $assignment->id,
            'notifiable_type' => "رفض مهمة إشعار لصاحب الصالة"
        ]
    );
}

    return response()->json([
        'status' => true,
        'message' => 'تم رفض المهمة بنجاح.',
        'data' => $assignment,
        'notification' => $notification
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
        ->where('status', '!=', 'pending')
        ->with(['service', 'reservation'])
        ->get();

    return response()->json([
        'status' => true,
        'message' => 'تم جلب المهام المنفذة أو المرفوضة.',
        'data' => $assignments,
    ]);
}

}
*/