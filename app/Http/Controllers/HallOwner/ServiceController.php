<?php

namespace App\Http\Controllers\HallOwner;
use App\Http\Controllers\Controller;
use App\Models\DecorationType;
use App\Models\Flower;
use App\Models\Hall;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Models\ServiceVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    private function storeSingleImage($file, string $directory): string
    {
        // تخزين الملف في مسار محدد
        $path = $file->store('public/' . $directory);

        // إرجاع المسار بعد استبدال 'public' بـ 'storage' ليتم استخدامه في الرابط
        return str_replace('public/', 'storage/', $path);
    }
    
    private function deleteOldImages(Hall $hall, array $imageColumns)
    {
        foreach ($imageColumns as $column) {
            if ($hall->{$column}) {
                Storage::disk('public')->delete($hall->{$column});
            }
        }
    }

    public function storeService(Request $request)
{
    $request->validate([
        'name_ar' => 'required|string',
        'name_en' => 'required|string',
    ]);
    $service = Service::create($request->only('name_ar', 'name_en'));
    return response()->json([
        'status' => true,
        'message' => 'تم إنشاء الخدمة بنجاح.',
        'service' => $service
    ]);
    }

public function storeFoodCategory(Request $request)
    {
        try {
            $validated = $request->validate([
                'service_id' => 'required|exists:services,id',
                'name_ar' => 'required|string',
                'name_en' => 'required|string',
                'image_1' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $data = $request->only('service_id', 'name_ar', 'name_en');
            // حفظ الصورة بشكل صحيح وإضافة مسارها إلى مصفوفة البيانات
            $imagePath = $this->storeSingleImage($request->file('image_1'), 'service_categories');
            $data['image_1'] = $imagePath;

            // إنشاء صنف الخدمة في قاعدة البيانات
            $category = ServiceCategory::create($data);

            return response()->json([
                'status' => true,
                'message' => 'تم إنشاء صنف الطعام بنجاح.',
                'category' => $category
            ], 201);

        } catch (ValidationException $e) {
            Log::error('Validation error storing food category: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing food category: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إنشاء صنف الطعام.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeFoodVariants(Request $request)
    {
        try {
            $validated = $request->validate([
                'service_category_id' => 'required|exists:service_categories,id',
                'name_ar' => 'required|string',
                'name_en' => 'required|string',
                'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            // حفظ الصورة وإضافة المسار إلى البيانات الموثقة
            $imagePath = $this->storeSingleImage($request->file('image'), 'service_variants');
            $validated['image'] = $imagePath;

            // إنشاء السجل باستخدام البيانات الموثقة الكاملة
            $variant = ServiceVariant::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'تم إنشاء نوع الطعام (Variant) بنجاح.',
                'variant' => $variant
            ], 201);

        } catch (ValidationException $e) {
            Log::error('Validation error storing food variant: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing food variant: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إنشاء نوع الطعام.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeFoodTypes(Request $request)
    {
        try {
            $validated = $request->validate([
                'service_variant_id' => 'required|exists:service_variants,id',
                'name_ar' => 'required|string',
                'name_en' => 'required|string',
                'price' => 'required|numeric|min:0',
                'description' => 'nullable|string|max:1000',
                'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            // حفظ الصورة وإضافة المسار إلى البيانات الموثقة
            $imagePath = $this->storeSingleImage($request->file('image'), 'service_types');
            $validated['image'] = $imagePath;

            // إنشاء السجل باستخدام البيانات الموثقة الكاملة
            $type = ServiceType::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'تم إنشاء صنف الطعام (Type) بنجاح.',
                'type' => $type
            ], 201);

        } catch (ValidationException $e) {
            Log::error('Validation error storing food type: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing food type: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إنشاء صنف الطعام.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeDecorationType(Request $request)
    {
        try {
            $validated = $request->validate([
                'service_id' => 'required|exists:services,id',
                'name_ar' => 'required|string',
                'name_en' => 'required|string',
                'image' => 'required|image|mimes:jpeg,png,jpg|max:2048', // تم تغيير 'image_1' إلى 'image'
            ]);

            // حفظ الصورة وإضافة المسار إلى البيانات الموثقة
            $imagePath = $this->storeSingleImage($request->file('image'), 'decoration_types');
            $validated['image'] = $imagePath;

            // إنشاء السجل باستخدام البيانات الموثقة الكاملة
            $type = DecorationType::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'تمت إضافة نوع الزينة بنجاح.',
                'type' => $type
            ], 201);

        } catch (ValidationException $e) {
            Log::error('Validation error storing decoration type: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing decoration type: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إضافة نوع الزينة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeFlower(Request $request)
    {
        try {
            $validated = $request->validate([
                'decoration_type_id' => 'required|exists:decoration_types,id',
                'name_ar' => 'required|string',
                'name_en' => 'required|string',
                'color' => 'required|string|max:50',
                'price' => 'required|numeric|min:0',
                'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            // حفظ الصورة وإضافة المسار إلى البيانات الموثقة
            $imagePath = $this->storeSingleImage($request->file('image'), 'flowers');
            $validated['image'] = $imagePath;

            // إنشاء السجل باستخدام البيانات الموثقة الكاملة
            $flower = Flower::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'تمت إضافة الزهرة بنجاح.',
                'flower' => $flower
            ], 201);

        } catch (ValidationException $e) {
            Log::error('Validation error storing flower: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing flower: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إضافة الزهرة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



public function deleteService($id)
{
    try {
        $service = Service::findOrFail($id);
        $service->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف الخدمة بنجاح.',
        ]);
    } catch (\Exception $e) {
        Log::error('Error deleting service: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'فشل حذف الخدمة.',
        ], 500);
    }
}

public function deleteFoodCategory($id)
{
    try {
        $category = ServiceCategory::findOrFail($id);
        $category->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف صنف الطعام.',
        ]);
    } catch (\Exception $e) {
        Log::error('Error deleting food category: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'فشل حذف الصنف.',
        ], 500);
    }
}

public function deleteFoodVariant($id)
{
    try {
        $variant = ServiceVariant::findOrFail($id);
        $variant->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف نوع الطعام.',
        ]);
    } catch (\Exception $e) {
        Log::error('Error deleting food variant: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'فشل حذف النوع.',
        ], 500);
    }
}

public function deleteFoodType($id)
{
    try {
        $type = ServiceType::findOrFail($id);
        $type->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف عنصر الطعام.',
        ]);
    } catch (\Exception $e) {
        Log::error('Error deleting food type: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'فشل حذف الطعام.',
        ], 500);
    }
}

public function deleteDecorationType($id)
{
    try {
        $type = DecorationType::findOrFail($id);
        $type->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف نوع الزينة.',
        ]);
    } catch (\Exception $e) {
        Log::error('Error deleting decoration type: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'فشل حذف نوع الزينة.',
        ], 500);
    }
}

public function deleteFlower($id)
{
    try {
        $flower = Flower::findOrFail($id);

        // حذف الصورة إن وجدت
        if ($flower->image && Storage::disk('public')->exists($flower->image)) {
            Storage::disk('public')->delete($flower->image);
        }

        $flower->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف الزهرة.',
        ]);
    } catch (\Exception $e) {
        Log::error('Error deleting flower: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'فشل حذف الزهرة.',
        ], 500);
    }
}

    public function getHallServices($hallId)
    {
        try {
            $hall = Hall::where('id', $hallId)
                        ->where('user_id', auth()->id()) // التأكد أن الصالة تابعة لمالك الصالة الحالي
                        ->with(['services' => function($query) {
                            $query->select('services.id', 'name_ar', 'name_en'); // جلب فقط الاسم والمعرف
                        }])
                        ->first();
            if (!$hall) {
                return response()->json([
                    'status' => false,
                    'message' => 'الصالة غير موجودة أو لا تملك صلاحية الوصول إليها.',
                ], 404);
            }
            if ($hall->services->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا توجد خدمات مرتبطة بهذه الصالة حالياً.',
                    'services' => [],
                ], 200);
            }
            return response()->json([
                'status' => true,
                'message' => 'تم جلب خدمات الصالة بنجاح.',
                'services' => $hall->services,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching hall services for owner: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب خدمات الصالة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function attachService(Request $request)
    {
        try {
            $request->validate([
                'hall_id' => 'required|exists:halls,id',
                'service_id' => 'required|exists:services,id',
            ]);
            $hall = Hall::where('id', $request->hall_id)
                        ->where('user_id', auth()->id())
                        ->first();
            if (!$hall) {
                return response()->json([
                    'status' => false,
                    'message' => 'الصالة غير موجودة أو لا تملك صلاحية ربط الخدمات بها.',
                ], 404);
            }
            if ($hall->services()->where('service_id', $request->service_id)->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'هذه الخدمة مرتبطة بالصالة بالفعل.',
                ], 409); // 409 Conflict
            }
            $hall->services()->attach($request->service_id);
            return response()->json([
                'status' => true,
                'message' => 'تم ربط الخدمة بالصالة بنجاح.',
            ], 200);
        } catch (ValidationException $e) {
            Log::error('Validation error attaching service to hall: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error attaching service to hall: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء ربط الخدمة بالصالة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function detachService(Request $request)
    {
        try {
            $request->validate([
                'hall_id' => 'required|exists:halls,id',
                'service_id' => 'required|exists:services,id',
            ]);
            $hall = Hall::where('id', $request->hall_id)
                        ->where('user_id', auth()->id())
                        ->first();
            if (!$hall) {
                return response()->json([
                    'status' => false,
                    'message' => 'الصالة غير موجودة أو لا تملك صلاحية فصل الخدمات عنها.',
                ], 404);
            }
            if (!$hall->services()->where('service_id', $request->service_id)->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'هذه الخدمة غير مرتبطة بالصالة أصلاً.',
                ], 404);
            }
            $hall->services()->detach($request->service_id);
            return response()->json([
                'status' => true,
                'message' => 'تم فصل الخدمة عن الصالة بنجاح.',
            ], 200);
        } catch (ValidationException $e) {
            Log::error('Validation error detaching service from hall: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error detaching service from hall: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء فصل الخدمة عن الصالة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}