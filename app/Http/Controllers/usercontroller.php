<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User ;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|digits:10|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'password' => Hash::make($validatedData['password']),
            'role' => 'user',

        ]);
        $token = $user->createToken($user->name . 'auth_token')->plainTextToken;
        return response()->json([
            'status'=>true,
            'message' => 'Registration successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    public function login (Request $request) {
        $credentials = $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);
        $user = User::where('email', $credentials['email'])->first();
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials ',
            ], 401);
        }
            $token = $user->createToken($user->name . 'auth_token')->plainTextToken;
            return response()->json([
                'status'=>true,
                'message' => 'Login successful',
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);
    }

    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ], 200);
    }

public function uploadImage(Request $request)
{
    $request->validate([
        'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
    ]);
    $user = auth()->user();
    if ($user->image) {
        Storage::delete('public/' . $user->image);
    }
    $path = $request->file('image')->store('user_images', 'public');
    $user->update([
        'image' => $path,
    ]);
    return response()->json([
        'message' => 'تم تحديث الصورة بنجاح',
        'image_url' => asset('storage/' . $path),
    ]);
}


public function updateProfile(Request $request)
{
    $user = auth()->user();

    $validated = $request->validate([
        'name' => 'sometimes|string|max:255',
        'email' => 'sometimes|email|unique:users,email,' . $user->id,
        'bio' => 'nullable|string|max:1000',
        // تحقق من كلمة المرور الحالية فقط إذا المستخدم طلب تغييرها
        'password' => 'required_with:new_password|string',
        'new_password' => 'nullable|string|min:8|confirmed',
    ]);

    // إذا طلب تغيير كلمة المرور
    if ($request->filled('new_password')) {
        // تحقق من صحة كلمة المرور الحالية
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'كلمة المرور الحالية غير صحيحة',
            ], 403);
        }

        // خزّن كلمة المرور الجديدة
        $validated['password'] = Hash::make($request->new_password);
    } else {
        // إزالة كلمة المرور من الـ validated إذا لم يتم إرسالها
        unset($validated['password']);
    }

    $user->update($validated);

    return response()->json([
        'message' => 'تم تحديث الملف الشخصي بنجاح',
        'user' => $user,
    ]);
}

public function profile()
{
    $user = auth()->user();

    return response()->json([
        'user' => $user
    ]);
}

}