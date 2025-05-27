<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\SearchHistory;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    public function searchEvents(Request $request)
    {
        try {
            $query = $request->input('query');
            if (empty($query)) {
                return response()->json([
                    'status' => false,
                    'message' => 'يرجى إدخال كلمة للبحث عن الأحداث.',
                ], 400);
            }
            $events = EventType::where('name_ar', 'like', "%$query%")
                ->orWhere('name_en', 'like', "%$query%")
                ->get();
            if (auth()->check()) { // حفظ سجل البحث فقط إذا كان المستخدم مسجلاً دخوله
                SearchHistory::create([
                    'user_id' => auth()->id(),
                    'keyword' => $query,
                    'type' => 'event',
                ]);
            }
            return response()->json([
                'status' => true,
                'message' => 'نتائج البحث عن الأحداث.',
                'events' => $events,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error searching events: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء البحث عن الأحداث.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function searchHalls(Request $request)
    {
        try {
            $query = $request->input('query');
            if (empty($query)) { // يمكن تبسيط هذا الشرط
                return response()->json([
                    'status' => false,
                    'message' => 'يرجى إدخال كلمة للبحث عن الصالات.',
                ], 400);
            }
            $halls = Hall::where('name_ar', 'like', "%$query%")
                ->orWhere('name_en', 'like', "%$query%")
                ->get();
            if (auth()->check()) { // حفظ سجل البحث فقط إذا كان المستخدم مسجلاً دخوله
                SearchHistory::create([
                    'user_id' => auth()->id(),
                    'keyword' => $query,
                    'type' => 'hall',
                ]);
            }
            return response()->json([
                'status' => true,
                'message' => 'نتائج البحث عن الصالات.',
                'halls' => $halls,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error searching halls: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء البحث عن الصالات.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function searchHistory()
    {
        try {
            $user = auth()->user();
            $history = $user->searchHistories()->latest()->take(20)->get();
            return response()->json([
                'status' => true,
                'history' => $history->map(function ($item) {
                    return [
                        'keyword' => $item->keyword,
                        'type' => $item->type,
                        'searched_at' => $item->created_at->diffForHumans(),
                    ];
                })
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching search history: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب سجل البحث.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
