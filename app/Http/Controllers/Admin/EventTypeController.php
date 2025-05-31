<?php

namespace App\Http\Controllers\Admin; // Namespace الصحيح

use App\Http\Controllers\Controller;
use App\Models\EventType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class EventTypeController extends Controller
{
    private function storeImage($file)
    {
        if ($file) {
            return $file->store('event_type_images', 'public');
        }
        return null;
    }

    public function createEventType(Request $request)
    {
        try {
            $validated = $request->validate([
                'name_ar' => 'required|string|max:255|unique:event_types,name_ar',
                'name_en' => 'required|string|max:255|unique:event_types,name_en',
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);
            $event = new EventType($validated);
            if ($request->hasFile('image')) {
                $event->image = $this->storeImage($request->file('image'));
            }
            $event->save();

            return response()->json([
                'status' => true,
                'message' => 'تم إنشاء نوع الحدث بنجاح.',
                'event_type' => $event,
            ], 201);
        } catch (ValidationException $e) {
            Log::error('Validation error creating event type: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating event type: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إنشاء نوع الحدث.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteEventType($id) // تم التغيير للحذف بالـ ID
    {
        try {
            $eventType = EventType::find($id); // استخدام find بدلاً من whereByName

            if (!$eventType) {
                return response()->json([
                    'status' => false,
                    'message' => 'نوع الحدث غير موجود.',
                ], 404);
            }

            // حذف الصورة المرتبطة إذا كانت موجودة
            if ($eventType->image) {
                Storage::disk('public')->delete($eventType->image);
            }

            $eventType->delete();

            return response()->json([
                'status' => true,
                'message' => 'تم حذف نوع الحدث بنجاح.',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting event type: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء حذف نوع الحدث.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function index()
    {
        try {
            $eventTypes = EventType::all();

            if ($eventTypes->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا توجد أنواع أحداث متاحة حالياً.',
                    'event_types' => [],
                ], 200);
            }

            return response()->json([
                'status' => true,
                'message' => 'تم جلب أنواع الأحداث بنجاح.',
                'event_types' => $eventTypes,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching event types: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب أنواع الأحداث.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
