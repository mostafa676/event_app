<?php


namespace App\Http\Controllers\HallOwner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CoordinatorAssignment; // استيراد نموذج مهمة المنسقين
use App\Models\Reservation;         // استيراد نموذج الحجوزات
use App\Models\Service;             // استيراد نموذج الخدمات
use App\Models\User;                // استيراد نموذج المستخدمين (للمنسق وصاحب الصالة)
use Illuminate\Support\Facades\Auth; // لاستخدام المستخدم الحالي (صاحب الصالة)
use Illuminate\Support\Facades\Validator; // للتحقق من صحة البيانات المدخلة

class CoordinatorAssignmentController extends Controller
{
    /**
     * تعيين مهمة جديدة لمنسق.
     * هذه الدالة يمكن أن تستخدمها مالكة الصالة لتعيين مهمة لمنسق.
     */
    public function assignTask(Request $request)
    {
        // 1. التحقق من صحة البيانات المدخلة (Validation)
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|exists:reservation,id', // يجب أن يكون موجوداً في جدول 'reservation'
            'service_id'     => 'nullable|exists:services,id',   // يمكن أن يكون فارغاً، ولكن إذا وُجد يجب أن يكون موجوداً في جدول 'services'
            'coordinator_id' => 'required|exists:users,id',      // يجب أن يكون موجوداً في جدول 'users'
            'instructions'   => 'nullable|string',               // تعليمات، يمكن أن تكون نصاً أو فارغة
        ], [
            'reservation_id.required' => 'معرف الحجز مطلوب.',
            'reservation_id.exists'   => 'معرف الحجز غير موجود.',
            'service_id.exists'       => 'معرف الخدمة غير موجود.',
            'coordinator_id.required' => 'معرف المنسق مطلوب.',
            'coordinator_id.exists'   => 'معرف المنسق غير موجود.',
            'instructions.string'     => 'التعليمات يجب أن تكون نصاً.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'بيانات الإدخال غير صالحة', 'errors' => $validator->errors()], 422);
        }

        // 2. التحقق من صلاحيات المستخدم (Authorization)
        // تأكدي أن المستخدم الحالي هو "صاحب صالة" (hall_owner)
        $assignedBy = Auth::user();

        if (!$assignedBy || $assignedBy->role !== 'hall_owner') {
            return response()->json(['status' => false, 'message' => 'غير مصرح لك بتعيين المهام.'], 403);
        }

        // 3. التحقق من أن المنسق المُعين هو فعلاً منسق
        $coordinator = User::find($request->coordinator_id);
        if (!$coordinator || $coordinator->role !== 'coordinator') {
            return response()->json(['status' => false, 'message' => 'المستخدم المحدد ليس منسقاً.'], 400);
        }

        // 4. إنشاء المهمة في قاعدة البيانات
        try {
            $assignment = CoordinatorAssignment::create([
                'reservation_id' => $request->reservation_id,
                'service_id'     => $request->service_id,
                'coordinator_id' => $request->coordinator_id,
                'assigned_by'    => $assignedBy->id, // ID صاحب الصالة الذي قام بالتعيين
                'instructions'   => $request->instructions,
                'status'         => 'pending', // الحالة الافتراضية عند التعيين
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم تعيين المهمة بنجاح.',
                'assignment' => $assignment
            ], 201); // 201 Created

        } catch (\Exception $e) {
            // في حال حدوث أي خطأ غير متوقع أثناء الحفظ
            return response()->json(['status' => false, 'message' => 'حدث خطأ أثناء تعيين المهمة: ' . $e->getMessage()], 500);
        }
    }

    // يمكنك إضافة دوال أخرى هنا لاحقاً:
    // - public function updateTaskStatus(Request $request, $id) { ... } // لتحديث حالة مهمة
    // - public function getCoordinatorTasks() { ... } // لجلب مهام منسق معين
    // - public function getReservationAssignments($reservationId) { ... } // لجلب مهام حجز معين
}

