<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function addToFavorites(Request $request)
{
    $request->validate([
        'favoritable_id' => 'required|integer',
        'favoritable_type' => 'required|string|in:hall,service',
    ]);

    $user = auth()->user();

    $favoritableType = $request->favoritable_type === 'hall' 
        ? \App\Models\Hall::class 
        : \App\Models\Service::class;

    $exists = $user->favorites()
        ->where('favoritable_id', $request->favoritable_id)
        ->where('favoritable_type', $favoritableType)
        ->exists();

    if ($exists) {
        return response()->json(['message' => 'هذا العنصر موجود بالفعل في المفضلة.'], 409);
    }

    $user->favorites()->create([
        'favoritable_id' => $request->favoritable_id,
        'favoritable_type' => $favoritableType,
    ]);

    return response()->json(['message' => 'تمت إضافة العنصر إلى المفضلة بنجاح.']);
}


public function removeFromFavorites(Request $request)
{
    $request->validate([
        'favoritable_id' => 'required|integer',
        'favoritable_type' => 'required|string|in:hall,service',
    ]);

    $user = auth()->user();

    $favoritableType = $request->favoritable_type === 'hall' 
        ? \App\Models\Hall::class 
        : \App\Models\Service::class;

    $deleted = $user->favorites()
        ->where('favoritable_id', $request->favoritable_id)
        ->where('favoritable_type', $favoritableType)
        ->delete();

    if ($deleted) {
        return response()->json(['message' => 'تم حذف العنصر من المفضلة بنجاح.']);
    } else {
        return response()->json(['message' => 'العنصر غير موجود في المفضلة.'], 404);
    }
}


public function listFavorites()
{
    $user = auth()->user();

    $favorites = $user->favorites()->with('favoritable')->get();

    $formattedFavorites = $favorites->map(function ($favorite) {
        return [
            'id' => $favorite->favoritable_id,
            'type' => class_basename($favorite->favoritable_type), // Hall أو Service
            'details' => $favorite->favoritable, // بيجيب تفاصيل الخدمة أو الصالة
        ];
    });

    return response()->json([
        'favorites' => $formattedFavorites,
    ]);
}

}