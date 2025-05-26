<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Service;
use App\Models\ServiceVariant;
use Illuminate\Support\Facades\Storage;

class AdminContentController extends Controller
{   private function storeImage($file)
    {
        return $file->store('images', 'public');
    }


    // some takses from siper admin
    public function createEventType(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required',
            'name_en' => 'required',
            'image' => 'nullable|image|max:2048',
        ]);
        $event = new EventType($validated);
        if ($request->hasFile('image')) {
            $event->image = $this->storeImage($request->file('image'));
        }
        $event->save();
        return response()->json($event);
    }

// 
    public function createHall(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required',
            'name_en' => 'required',
            'event_type_id' => 'required|exists:event_types,id',
            'location_ar' => 'required',
            'location_en' => 'required',
            'capacity' => 'required|integer',
            'price' => 'required|numeric',
            'image' => 'nullable|image|max:2048',
        ]);
        $hall = new Hall($validated);
        if ($request->hasFile('image')) {
            $hall->image = $this->storeImage($request->file('image'));
        }
        $hall->save();
        return response()->json($hall);
    }
    public function createService(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required',
            'name_en' => 'required',
            'avg_price' => 'required|numeric',
            'image' => 'nullable|image|max:2048',
        ]);
        $service = new Service($validated);
        if ($request->hasFile('image')) {
            $service->image = $this->storeImage($request->file('image'));
        }
        $service->save();
        return response()->json($service);
    }
    public function createServiceVariant(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'name_ar' => 'required',
            'name_en' => 'required',
            'color' => 'nullable|string',
            'price' => 'required|numeric',
            'image' => 'nullable|image|max:2048',
        ]);
 $variant = new ServiceVariant($validated);
        if ($request->hasFile('image')) {
            $variant->image = $this->storeImage($request->file('image'));
        }
        $variant->save();
        return response()->json($variant);
    }

    public function deleteEventTypeByName($name)
{
    $eventType = EventType::where('name_ar', $name)->orWhere('name_en', $name)->first();
    if (!$eventType) {
        return response()->json(['message' => 'Event type not found'], 404);
    }
    $eventType->delete();
    return response()->json(['message' => 'Event type deleted successfully']);
}
public function deleteHallByName($name)
{
    $hall = Hall::where('name_ar', $name)->orWhere('name_en', $name)->first();
    if (!$hall) {
        return response()->json(['message' => 'Hall not found'], 404);
    }
    $hall->delete();
    return response()->json(['message' => 'Hall deleted successfully']);
}
public function deleteServiceByName($name)
{
    $service = Service::where('name_ar', $name)->orWhere('name_en', $name)->first();
    if (!$service) {
        return response()->json(['message' => 'Service not found'], 404);
    }
    $service->delete();
    return response()->json(['message' => 'Service deleted successfully']);
}
public function deleteServiceVariantByName($name)
{
    $variant = ServiceVariant::where('name_ar', $name)->orWhere('name_en', $name)->first();
    if (!$variant) {
        return response()->json(['message' => 'Service variant not found'], 404);
    }
    $variant->delete();
    return response()->json(['message' => 'Service variant deleted successfully']);
}
}
