<?php

namespace App\Http\Controllers\Admin; 

use App\Http\Controllers\Controller;
use App\Models\User; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class HallOwnerController extends Controller
{
    public function index()
    {
        try {
            $hallOwners = User::where('role', 'hall_owner')->get();
            if ($hallOwners->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا يوجد مالكي صالات مسجلين حالياً.',
                    'hall_owners' => [],
                ], 200);
            }
            return response()->json([
                'status' => true,
                'message' => 'تم جلب مالكي الصالات بنجاح.',
                'hall_owners' => $hallOwners,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching hall owners: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب مالكي الصالات.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id) 
    {
        try {
            $hallOwner = User::where('id', $id)->where('role', 'hall_owner')->first();
            if (!$hallOwner) {
                return response()->json([
                    'status' => false,
                    'message' => 'مالك الصالة غير موجود بهذا الرقم أو ليس لديه هذا الدور.', // رسالة معدلة
                ], 404);
            }
            return response()->json([
                'status' => true,
                'message' => 'تم جلب تفاصيل مالك الصالة بنجاح.',
                'hall_owner' => $hallOwner,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching specific hall owner by phone: ' . $e->getMessage()); // رسالة Log معدلة
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب تفاصيل مالك الصالة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createHallOwner(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'phone' => 'required|digits:10|unique:users,phone',
                'password' => 'required|string|min:8|confirmed',
            ]);
            $hallOwner = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'password' => Hash::make($validatedData['password']),
                'role' => 'hall_owner', // تعيين الدور كـ 'hall_owner'
            ]);
            return response()->json([
                'status' => true,
                'message' => 'تم إنشاء حساب مالك الصالة بنجاح.',
                'hall_owner' => $hallOwner,
            ], 201); // 201 Created

        } catch (ValidationException $e) {
            Log::error('Validation error creating hall owner: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating hall owner: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إنشاء حساب مالك الصالة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteHallOwner($id)
    {
        try {
            $hallOwner = User::where('id', $id)->where('role', 'hall_owner')->first();
            if (!$hallOwner) {
                return response()->json([
                    'status' => false,
                    'message' => 'مالك الصالة غير موجود أو ليس لديه هذا الدور.',
                ], 404);
            }
            $hallOwner->delete();
            return response()->json([
                'status' => true,
                'message' => 'تم حذف مالك الصالة بنجاح.',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting hall owner: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء حذف مالك الصالة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
