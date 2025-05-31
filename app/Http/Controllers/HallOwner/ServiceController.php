<?php

namespace App\Http\Controllers\HallOwner; // Namespace الصحيح

use App\Http\Controllers\Controller;
use App\Models\Hall; // لاستخدام Hall model
use App\Models\Service; // لاستخدام Service model
use App\Models\ServiceVariant; // لاستخدام ServiceVariant model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    private function storeImage($file)
    {
        if ($file) {
            return $file->store('service_variant_images', 'public'); 
        }
        return null;
    }

    private function deleteOldImage(?string $path)
    {
        if ($path) {
            Storage::disk('public')->delete($path);
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

    public function getServiceVariants($serviceId)
    {
        try {
            $service = Service::where('id', $serviceId)
                              ->whereHas('halls', function ($query) {
                                  $query->where('user_id', auth()->id());
                              })
                              ->with('variants')
                              ->first();
            if (!$service) {
                return response()->json([
                    'status' => false,
                    'message' => 'الخدمة غير موجودة أو غير مرتبطة بأي من صالاتك.',
                ], 404);
            }
            if ($service->variants->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا توجد أنواع (variants) لهذه الخدمة حالياً.',
                    'variants' => [],
                ], 200);
            }
            return response()->json([
                'status' => true,
                'message' => 'تم جلب أنواع الخدمة بنجاح.',
                'variants' => $service->variants,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching service variants for owner: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب أنواع الخدمة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeVariant(Request $request)
    {
        try {
            $request->validate([
                'service_id' => 'required|exists:services,id',
                'name_ar' => 'required|string|max:255',
                'name_en' => 'required|string|max:255',
                'color' => 'nullable|string|max:50',
                'price' => 'required|numeric|min:0',
                'description' => 'nullable|string|max:1000',
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);
            $service = Service::where('id', $request->service_id)
                              ->whereHas('halls', function ($query) {
                                  $query->where('user_id', auth()->id());
                              })
                              ->first();
            if (!$service) {
                return response()->json([
                    'status' => false,
                    'message' => 'الخدمة غير موجودة أو غير مرتبطة بأي من صالاتك، لا يمكن إضافة نوع لها.',
                ], 403); // 403 Forbidden
            }
            $variantData = $request->only(['service_id', 'name_ar', 'name_en', 'color', 'price', 'description']);
            if ($request->hasFile('image')) {
                $variantData['image'] = $this->storeImage($request->file('image'));
            }
            $variant = ServiceVariant::create($variantData);
            return response()->json([
                'status' => true,
                'message' => 'تم إضافة نوع الخدمة بنجاح.',
                'variant' => $variant,
            ], 201); // 201 Created
        } catch (ValidationException $e) {
            Log::error('Validation error storing service variant: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing service variant: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إضافة نوع الخدمة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateVariant(Request $request, $variantId)
    {
        try {
            $variant = ServiceVariant::find($variantId);
            if (!$variant) {
                return response()->json([
                    'status' => false,
                    'message' => 'نوع الخدمة الفرعي غير موجود.',
                ], 404);
            }
            if (!$variant->service || !$variant->service->halls()->where('user_id', auth()->id())->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا تملك صلاحية تعديل نوع الخدمة هذا.',
                ], 403); // 403 Forbidden
            }
            $request->validate([
                'name_ar' => 'required|string|max:255',
                'name_en' => 'required|string|max:255',
                'color' => 'nullable|string|max:50',
                'price' => 'required|numeric|min:0',
                'description' => 'nullable|string|max:1000',
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);
            $variantData = $request->only(['name_ar', 'name_en', 'color', 'price', 'description']);
            if ($request->hasFile('image')) {
                $this->deleteOldImage($variant->image); // حذف الصورة القديمة
                $variantData['image'] = $this->storeImage($request->file('image'));
            }
            $variant->update($variantData);
            return response()->json([
                'status' => true,
                'message' => 'تم تحديث نوع الخدمة بنجاح.',
                'variant' => $variant,
            ], 200);
        } catch (ValidationException $e) {
            Log::error('Validation error updating service variant: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating service variant: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تحديث نوع الخدمة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroyVariant($variantId)
    {
        try {
            $variant = ServiceVariant::find($variantId);
            if (!$variant) {
                return response()->json([
                    'status' => false,
                    'message' => 'نوع الخدمة الفرعي غير موجود.',
                ], 404);
            }
            if (!$variant->service || !$variant->service->halls()->where('user_id', auth()->id())->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا تملك صلاحية حذف نوع الخدمة هذا.',
                ], 403); // 403 Forbidden
            }
            $this->deleteOldImage($variant->image); // حذف الصورة المرتبطة
            $variant->delete();
            return response()->json([
                'status' => true,
                'message' => 'تم حذف نوع الخدمة بنجاح.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error destroying service variant: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء حذف نوع الخدمة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
