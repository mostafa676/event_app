<?php

namespace App\Http\Controllers;

use App\Models\EventType;
use App\Models\Hall;
use App\Models\Service;
use Illuminate\Http\Request;
use App\Models\SearchHistory;


class SearchController extends Controller
{
    public function searchEvents(Request $request)
{
    $query = $request->input('query');

    if (empty($query)) {
        return response()->json(['message' => 'يرجى إدخال كلمة للبحث'], 400);
    }    

    $Events = EventType::where('name_ar', 'like', "%$query%")
                 ->orWhere('name_en', 'like', "%$query%")
                 ->get();

     SearchHistory::create([
        'user_id' => auth()->id(),
        'keyword' => $request->input('query'),
        'type' => 'event',
            ]);

    return response()->json($Events);
}
public function searchHalls(Request $request)
{
    $query = $request->input('query');
if (!$request->has('query') || empty($request->query)) {
        return response()->json(['message' => 'يرجى إدخال كلمة للبحث'], 400);
    }  
    $halls = Hall::where('name_ar', 'like', "%$query%")
                 ->orWhere('name_en', 'like', "%$query%")
                 ->get();

            SearchHistory::create([
        'user_id' => auth()->id(),
        'keyword' => $request->input('query'),
        'type' => 'hall',
            ]);     
    return response()->json($halls);
}
public function searchServices(Request $request)
{
    $query = $request->input('query');
    if (!$request->has('query') || empty($request->query)) {
        return response()->json(['message' => 'يرجى إدخال كلمة للبحث'], 400);
    }  
    $services = Service::where('name_ar', 'like', "%$query%")
                       ->orWhere('name_en', 'like', "%$query%")
                       ->get();
   SearchHistory::create([
        'user_id' => auth()->id(),
        'keyword' => $request->input('query'),
        'type' => 'service',
            ]);

    return response()->json($services);
}

public function searchHistory()
{
    $history = auth()->user()->searchHistories()->latest()->take(20)->get();

    return response()->json([
        'history' => $history->map(function($item) {
            return [
                'keyword' => $item->keyword,
                'type' => $item->type,
                'searched_at' => $item->created_at->diffForHumans(),
            ];
        })
    ]);
    
}

}
