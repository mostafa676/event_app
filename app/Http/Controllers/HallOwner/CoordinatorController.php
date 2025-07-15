<?php

namespace App\Http\Controllers\HallOwner;
use App\Http\Controllers\Controller;
use App\Models\User; 
use App\Models\Coordinator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class CoordinatorController extends Controller
{
    public function index()
    {
        try {
            $hallOwnerId = auth()->id();
            $coordinators = Coordinator::where('hall_owner_id', $hallOwnerId)
                                     ->with('user:id,name,email,phone') 
                                     ->get();
            if ($coordinators->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا يوجد منسقون مسجلون لديك حالياً.',
                    'coordinators' => [],
                ], 200);
            }
            return response()->json([
                'status' => true,
                'message' => 'تم جلب المنسقين بنجاح.',
                'coordinators' => $coordinators,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching hall owner coordinators: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب المنسقين.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function show($id)
    {
        try {
            $hallOwnerId = auth()->id();
            $coordinator = Coordinator::where('id', $id)
                                      ->where('hall_owner_id', $hallOwnerId)
                                      ->with('user:id,name,email,phone')
                                      ->first();
            if (!$coordinator) {
                return response()->json([
                    'status' => false,
                    'message' => 'المنسق غير موجود أو لا تملك صلاحية الوصول إليه.',
                ], 404);
            }
            return response()->json([
                'status' => true,
                'message' => 'تم جلب تفاصيل المنسق بنجاح.',
                'coordinator' => $coordinator,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching specific coordinator for owner: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب تفاصيل المنسق.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validate([
                'coordinator_type_id' => 'required|exists:coordinator_types,id',
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'phone' => 'required|digits:10|unique:users,phone',
                'password' => 'required|string|min:8|confirmed',
                'specialization' => 'required|string|max:255',
            ]);
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'password' => Hash::make($validatedData['password']),
                'role' => 'coordinator',
            ]);
             $coordinator = Coordinator::create([
            'user_id' => $user->id,
            'hall_owner_id' => auth()->id(),
            'specialization' => $validatedData['specialization'],
            'coordinator_type_id' => $validatedData['coordinator_type_id'], 
        ]);
        DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'تم إضافة المنسق بنجاح.',
                'coordinator' => $coordinator->load('user'), 
            ], 201); // 201 Created
        } catch (ValidationException $e) {
            DB::rollBack(); // التراجع عن المعاملة في حالة فشل التحقق من الصحة
            Log::error('Validation error creating coordinator: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack(); // التراجع عن المعاملة في حالة أي خطأ آخر
            Log::error('Error creating coordinator: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إضافة المنسق.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $hallOwnerId = auth()->id();
            $coordinator = Coordinator::where('id', $id)
                                      ->where('hall_owner_id', $hallOwnerId)
                                      ->first();
            if (!$coordinator) {
                return response()->json([
                    'status' => false,
                    'message' => 'المنسق غير موجود أو لا تملك صلاحية تعديله.',
                ], 404);
            }
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $coordinator->user_id, // تجاهل البريد الإلكتروني الحالي للمنسق
                'phone' => 'required|digits:10|unique:users,phone,' . $coordinator->user_id, // تجاهل رقم الهاتف الحالي للمنسق
                'password' => 'nullable|string', // لا يجب أن يكون مطلوبًا إلا إذا كان new_password موجودًا
                'new_password' => 'nullable|string|min:8|confirmed',
                'specialization' => 'required|string|max:255',
                'hourly_rate' => 'nullable|numeric|min:0',
            ]);
            $user = $coordinator->user; 
            if (!$user) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'حساب المستخدم المرتبط بالمنسق غير موجود.',
                ], 500);
            }
            $userData = $request->only(['name', 'email', 'phone']);
            if ($request->filled('new_password')) {
                $userData['password'] = Hash::make($request->new_password);
            }
            $user->update($userData);
            $coordinatorData = $request->only(['specialization', 'hourly_rate']);
            $coordinator->update($coordinatorData);
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'تم تحديث معلومات المنسق بنجاح.',
                'coordinator' => $coordinator->load('user'),
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Validation error updating coordinator: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating coordinator: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تحديث المنسق.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $hallOwnerId = auth()->id();
            $coordinator = Coordinator::where('id', $id)
                                      ->where('hall_owner_id', $hallOwnerId)
                                      ->first();
            if (!$coordinator) {
                return response()->json([
                    'status' => false,
                    'message' => 'المنسق غير موجود أو لا تملك صلاحية حذفه.',
                ], 404);
            }
            $userId = $coordinator->user_id;
            $coordinator->delete();
            $user = User::find($userId);
            if ($user) {
                $user->delete();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'تم حذف المنسق بنجاح.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting coordinator: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء حذف المنسق.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
