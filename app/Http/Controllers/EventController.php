<?php

namespace App\Http\Controllers;

use App\Models\EventType;
use App\Models\Hall;
use App\Models\Service;
use Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function getEventTypes(){
        $eventTypes = EventType::all();
        if (!$eventTypes){
            return response()->json([
                'message' => 'error',
            ], 200);
        }
        return response()->json([
            'message' => 'event retrieved successfully',
            'stores' => $eventTypes,
        ], 200);
    }

    public function getHallsByEvent($eventTypeId)
    {
        $halls = Hall::where('event_type_id', $eventTypeId)
                     ->select('id', 'name_ar', 'name_en', 'location_ar', 'location_en', 'capacity', 'price')
                     ->get();
                     if (!$halls){
                        return response()->json([
                            'message' => 'error',
                        ], 200);
                    }
                    return response()->json([
                        'message' => 'hall retrieved successfully',
                        'stores' => $halls,
                    ], 200);
    }

    public function getServicesByHall($hallId)
    {
        $hall = Hall::with(['services' => function ($query) {
            $query->select('services.id', 'name_ar', 'name_en', 'price_per_unit');
        }])->findOrFail($hallId);
                if (!$hall){
                        return response()->json([
                            'message' => 'error',
                        ], 200);
                    }
                    return response()->json([
                        'message' => 'hall services retrieved successfully',
                        'stores' => $hall->services,
                    ], 200);
        
    }

    public function getVariantsByService($serviceId)
{
    $service = Service::with(['variants' => function ($query) {
        $query->select('id', 'service_id', 'name_ar', 'name_en', 'color', 'price');
    }])->find($serviceId);

    if (!$service) {
        return response()->json([
            'message' => 'Service not found',
        ], 404);
    }

    return response()->json([
        'message' => 'Variants retrieved successfully',
        'variants' => $service->variants,
    ], 200);
}

    
}
