<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException; 

class AuthController extends Controller 
{

public function register(Request $request)
{
    try {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|digits:10|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'fcm_token' => 'nullable|string', // ← اختياري
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'password' => Hash::make($validatedData['password']),
            'role' => 'user',
            'fcm_token' => $validatedData['fcm_token'] ?? null, // ← نحفظه إذا موجود
        ]);

        $token = $user->createToken($user->name . 'auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Registration successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201); 

    } catch (ValidationException $e) {
        Log::error('Validation error during registration: ' . $e->getMessage(), ['errors' => $e->errors()]);
        return response()->json([
            'status' => false,
            'message' => 'فشل التحقق من صحة البيانات.',
            'errors' => $e->errors(),
        ], 422); 
    } catch (\Exception $e) {
        Log::error('Error during registration: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء عملية التسجيل.',
            'error' => $e->getMessage(),
        ], 500); 
    }
}

public function login(Request $request)
{
    try {
        $credentials = $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
            'fcm_token' => 'nullable|string', // ← اختياري
        ]);

        $user = User::where('email', $credentials['email'])->first();
//|| !Hash::check($credentials['password'], $user->password)
        if (!$user ) {
            return response()->json([
                'status' => false,
                'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
            ], 401); 
        }

        // إذا أرسل FCM Token نحفظه
        if (!empty($credentials['fcm_token'])) {
            $user->fcm_token = $credentials['fcm_token'];
            $user->save();
        }

        $user->tokens()->delete();
        $token = $user->createToken($user->name . 'auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200); 

    } catch (ValidationException $e) {
        Log::error('Validation error during login: ' . $e->getMessage(), ['errors' => $e->errors()]);
        return response()->json([
            'status' => false,
            'message' => 'فشل التحقق من صحة البيانات.',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('Error during login: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء عملية تسجيل الدخول.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function logout(Request $request)
{
    try {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم تسجيل الخروج بنجاح'
        ], 200);

    } catch (\Exception $e) {
        \Log::error('Error during logout: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء عملية تسجيل الخروج.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function changePassword(Request $request)
{
    try {
        // التحقق من صحة البيانات
        $validatedData = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);
        $user = $request->user();
        if (!Hash::check($validatedData['current_password'], $user->password)) {
            return response()->json([
                'status' => false, 'message' => 'كلمة المرور الحالية غير صحيحة.',
            ], 400);
        }
        $user->password = Hash::make($validatedData['new_password']);
        $user->save();
        return response()->json([
            'status' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح.',
        ], 200);
    } catch (ValidationException $e) {
        Log::error('Validation error during password change: ' . $e->getMessage(), ['errors' => $e->errors()]);
        return response()->json([      'status' => false,
            'message' => 'فشل التحقق من صحة البيانات.',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('Error during password change: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء تغيير كلمة المرور.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function uploadImage(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);
            $user = auth()->user();
            if ($user->image) {
                Storage::disk('public')->delete($user->image); // استخدام disk('public')
            }
            $path = $request->file('image')->store('user_images', 'public');
            $user->update([
                'image' => $path,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم تحديث الصورة بنجاح',
                'image_url' => asset('storage/' . $path),
            ], 200);

        } catch (ValidationException $e) {
            Log::error('Validation error during image upload: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error during image upload: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء رفع الصورة.',
                'error' => $e->getMessage(),
            ], 500);
        }
}
 
public function updateProfile(Request $request)
    {
        try {
            $user = auth()->user();
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'bio' => 'nullable|string|max:1000',
                'password' => 'nullable|string',
                'new_password' => 'nullable|string|min:8|confirmed', 
            ]);

            if ($request->filled('new_password')) {
                if (!Hash::check($request->password, $user->password)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'كلمة المرور الحالية غير صحيحة',
                    ], 403); 
                }
                $validated['password'] = Hash::make($request->new_password);
            } else {
                unset($validated['password']);
                unset($validated['new_password']); 
             }
            $user->update($validated);
            return response()->json([
                'status' => true,
                'message' => 'تم تحديث الملف الشخصي بنجاح',
                'user' => $user,
            ], 200);

        } catch (ValidationException $e) {
            Log::error('Validation error during profile update: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error during profile update: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تحديث الملف الشخصي.',
                'error' => $e->getMessage(),
            ], 500);
        }
}

public function profile()
    {
        try {
            $user = auth()->user();
            return response()->json([
                'status' => true,
                'user' => $user,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching user profile: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب الملف الشخصي.',
                'error' => $e->getMessage(),
            ], 500);
        }
}

public function saveFcmToken(Request $request)
{
    $request->validate(['token' => 'required|string']);
    $user = auth()->user();
    $user->fcm_token = $request->token;
    $user->save();

    return response()->json(['status' => true, 'message' => 'FCM token saved.']);
}

}
