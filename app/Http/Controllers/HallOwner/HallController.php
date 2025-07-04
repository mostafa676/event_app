<?php

namespace App\Http\Controllers\HallOwner; 

use App\Http\Controllers\Controller;
use App\Models\Hall; 
use App\Models\HallSchedule; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class HallController extends Controller
{

    private function storeImages(?array $files): array
    {
        $paths = [];
        if (!empty($files)) {
            foreach ($files as $index => $file) {
                if ($file) {
                    // تخزين الصورة في مجلد 'hall_images' داخل 'public'
                    $paths['image_' . ($index + 1)] = $file->store('hall_images', 'public');
                }
            }
        }
        return $paths;
    }

    private function deleteOldImages(Hall $hall, array $imageColumns)
    {
        foreach ($imageColumns as $column) {
            if ($hall->{$column}) {
                Storage::disk('public')->delete($hall->{$column});
            }
        }
    }

    public function index()
    {
        try {
            $halls = Hall::where('user_id', auth()->id())->get();

            if ($halls->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا توجد صالات مسجلة لهذا المالك حالياً.',
                    'halls' => [],
                ], 200);
            }
            return response()->json([
                'status' => true,
                'message' => 'تم جلب الصالات بنجاح.',
                'halls' => $halls,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching hall owner halls: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب الصالات.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $hall = Hall::where('id', $id)->where('user_id', auth()->id())->first();
            if (!$hall) {
                return response()->json([
                    'status' => false,
                    'message' => 'الصالة غير موجودة أو لا تملك صلاحية الوصول إليها.',
                ], 404);
            }
            return response()->json([
                'status' => true,
                'message' => 'تم جلب تفاصيل الصالة بنجاح.',
                'hall' => $hall,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching specific hall for owner: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب تفاصيل الصالة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name_ar' => 'required|string|max:255',
                'name_en' => 'required|string|max:255',
                'place_type_id' => 'required|exists:place_types,id',
                'location_ar' => 'required|string|max:255',
                'location_en' => 'required|string|max:255',
                'capacity' => 'required|integer|min:1',
                'price' => 'required|numeric|min:0',
                'images' => 'nullable|array|max:3',
                'images.*' => 'image|mimes:jpeg,png,jpg|max:2048', 
            ]);
            $hallData = [
                'user_id' => auth()->id(),
                'name_ar' => $validated['name_ar'],
                'name_en' => $validated['name_en'],
                'place_type_id' => $validated['place_type_id'],
                'location_ar' => $validated['location_ar'],
                'location_en' => $validated['location_en'],
                'capacity' => $validated['capacity'],
                'price' => $validated['price'],
            ];
            $imagePaths = $this->storeImages($request->file('images'));
            $hallData = array_merge($hallData, $imagePaths); 
            $hall = Hall::create($hallData);
            return response()->json([
                'status' => true,
                'message' => 'تم إضافة الصالة بنجاح.',
                'hall' => $hall,
            ], 201); // 201 Created
        } catch (ValidationException $e) {
            Log::error('Validation error creating hall: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating hall: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إضافة الصالة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $hall = Hall::where('id', $id)->where('user_id', auth()->id())->first();
            if (!$hall) {
                return response()->json([
                    'status' => false,
                    'message' => 'الصالة غير موجودة أو لا تملك صلاحية تعديلها.',
                ], 404);
            }
            $validated = $request->validate([
                'name_ar' => 'required|string|max:255',
                'name_en' => 'required|string|max:255',
                'event_type_id' => 'required|exists:event_types,id',
                'location_ar' => 'required|string|max:255',
                'location_en' => 'required|string|max:255',
                'capacity' => 'required|integer|min:1',
                'price' => 'required|numeric|min:0',
                'images' => 'nullable|array|max:3',
                'images.*' => 'image|mimes:jpeg,png,jpg|max:2048', 
            ]);
            $hallData = [
                'name_ar' => $validated['name_ar'],
                'name_en' => $validated['name_en'],
                'event_type_id' => $validated['event_type_id'],
                'location_ar' => $validated['location_ar'],
                'location_en' => $validated['location_en'],
                'capacity' => $validated['capacity'],
                'price' => $validated['price'],
            ];
            if ($request->hasFile('images')) {
                $this->deleteOldImages($hall, ['image_1', 'image_2', 'image_3']);
                $imagePaths = $this->storeImages($request->file('images'));
                $hallData = array_merge($hallData, $imagePaths);
            }
            $hall->update($hallData);
            return response()->json([
                'status' => true,
                'message' => 'تم تحديث معلومات الصالة بنجاح.',
                'hall' => $hall,
            ], 200);
        } catch (ValidationException $e) {
            Log::error('Validation error updating hall: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating hall: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تحديث الصالة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $hall = Hall::where('id', $id)->where('user_id', auth()->id())->first();
            if (!$hall) {
                return response()->json([
                    'status' => false,
                    'message' => 'الصالة غير موجودة أو لا تملك صلاحية حذفها.',
                ], 404);
            }
            $this->deleteOldImages($hall, ['image_1', 'image_2', 'image_3']);
            $hall->delete();
            return response()->json([
                'status' => true,
                'message' => 'تم حذف الصالة بنجاح.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting hall: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء حذف الصالة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateHallSchedule(Request $request, $hallId)
    {
        try {
            $hall = Hall::where('id', $hallId)->where('user_id', auth()->id())->first();
            if (!$hall) {
                return response()->json([
                    'status' => false,
                    'message' => 'الصالة غير موجودة أو لا تملك صلاحية تعديل جدولها.',
                ], 404);
            }

            $request->validate([
                'schedules' => 'required|array',
                'schedules.*.day_of_week' => 'required|string|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
                // القواعد المعدلة: start_time و end_time مطلوبة فقط إذا كانت is_available = true
                'schedules.*.start_time' => 'required_if:schedules.*.is_available,true|nullable|date_format:H:i',
                'schedules.*.end_time' => 'required_if:schedules.*.is_available,true|nullable|date_format:H:i|after:schedules.*.start_time',
                'schedules.*.is_available' => 'boolean', // اختياري، الافتراضي true
            ]);

            foreach ($request->schedules as $scheduleData) {
                HallSchedule::updateOrCreate(
                    [
                        'hall_id' => $hall->id,
                        'day_of_week' => $scheduleData['day_of_week'],
                    ],
                    [
                        'start_time' => $scheduleData['is_available'] ? $scheduleData['start_time'] : null, // قم بتعيين null إذا كانت غير متاحة
                        'end_time' => $scheduleData['is_available'] ? $scheduleData['end_time'] : null,     // قم بتعيين null إذا كانت غير متاحة
                        'is_available' => $scheduleData['is_available'] ?? true,
                    ]
                );
            }

            $hall->load('schedules'); 
            return response()->json([
                'status' => true,
                'message' => 'تم تحديث جدول أوقات الصالة بنجاح.',
                'hall_schedules' => $hall->schedules,
            ], 200);

        } catch (ValidationException $e) {
            Log::error('Validation error updating hall schedule: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating hall schedule: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تحديث جدول الصالة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
