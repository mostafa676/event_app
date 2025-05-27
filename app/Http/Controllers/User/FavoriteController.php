<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hall; 
use App\Models\Service;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class FavoriteController extends Controller
{
  
    public function addToFavorites(Request $request)
    {
        try {
            $request->validate([
                'favoritable_id' => 'required|integer',
                'favoritable_type' => 'required|string|in:hall,service',
            ]);
            $user = auth()->user();
            $favoritableTypeModel = null;
            if ($request->favoritable_type === 'hall') {
                $favoritableTypeModel = Hall::class;
            } elseif ($request->favoritable_type === 'service') {
                $favoritableTypeModel = Service::class;
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'نوع العنصر غير صالح.'
                ], 400);
            }
            if (!($favoritableTypeModel::find($request->favoritable_id))) {
                return response()->json([
                    'status' => false,
                    'message' => 'العنصر المحدد غير موجود.'
                ], 404);
            }
            $exists = $user->favorites()
                ->where('favoritable_id', $request->favoritable_id)
                ->where('favoritable_type', $favoritableTypeModel)
                ->exists();
            if ($exists) {
                return response()->json([
                    'status' => false,
                    'message' => 'هذا العنصر موجود بالفعل في المفضلة.'
                ], 409); // 409 Conflict
            }
            $user->favorites()->create([
                'favoritable_id' => $request->favoritable_id,
                'favoritable_type' => $favoritableTypeModel,
            ]);
            return response()->json([
                'status' => true,
                'message' => 'تمت إضافة العنصر إلى المفضلة بنجاح.'
            ], 201); // 201 Created
        } catch (ValidationException $e) {
            Log::error('Validation error adding to favorites: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error adding to favorites: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إضافة العنصر إلى المفضلة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function removeFromFavorites(Request $request)
    {
        try {
            $request->validate([
                'favoritable_id' => 'required|integer',
                'favoritable_type' => 'required|string|in:hall,service',
            ]);
            $user = auth()->user();
            $favoritableTypeModel = null;
            if ($request->favoritable_type === 'hall') {
                $favoritableTypeModel = Hall::class;
            } elseif ($request->favoritable_type === 'service') {
                $favoritableTypeModel = Service::class;
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'نوع العنصر غير صالح.'
                ], 400);
            }
            $deleted = $user->favorites()
                ->where('favoritable_id', $request->favoritable_id)
                ->where('favoritable_type', $favoritableTypeModel)
                ->delete();
            if ($deleted) {
                return response()->json([
                    'status' => true,
                    'message' => 'تم حذف العنصر من المفضلة بنجاح.'
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'العنصر غير موجود في المفضلة أو لا ينتمي للمستخدم.',
                ], 404);
            }
        } catch (ValidationException $e) {
            Log::error('Validation error removing from favorites: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error removing from favorites: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء حذف العنصر من المفضلة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function listFavorites()
    {
        try {
            $user = auth()->user();
            $favorites = $user->favorites()->with('favoritable')->get();
            $formattedFavorites = $favorites->map(function ($favorite) {
                // التأكد من أن favoritable ليس null (في حال حذف العنصر الأصلي)
                if (!$favorite->favoritable) {
                    return null; // يمكن التعامل مع هذا حسب الرغبة (مثلاً حذف العنصر المفضلة)
                }
                return [
                    'id' => $favorite->favoritable_id,
                    'type' => class_basename($favorite->favoritable_type), // Hall أو Service
                    'details' => $favorite->favoritable, // يجلب تفاصيل الخدمة أو الصالة
                ];
            })->filter()->values(); // لإزالة أي عناصر null وإعادة ترتيب المفاتيح
            return response()->json([
                'status' => true,
                'favorites' => $formattedFavorites,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error listing favorites: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب قائمة المفضلة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}