<?php

namespace App\Http\Controllers\User; 

use App\Http\Controllers\Controller;
use App\Models\Hall;
use App\Models\EventType;
use App\Models\Service;
use App\Models\ServiceVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException; 

class HallController extends Controller
{

     public function show($hallId)
    {
        try {
            $hall = Hall::with(['eventType', 'services' => function ($query) {
                $query->select('services.id', 'name_ar', 'name_en');
            }])->find($hallId);
            if (!$hall) {
                return response()->json([
                    'status' => false,
                    'message' => 'الصالة المطلوبة غير موجودة.',
                ], 404);
            }
            return response()->json([
                'status' => true,
                'message' => 'تم جلب تفاصيل الصالة بنجاح.',
                'hall' => $hall,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching specific hall: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب تفاصيل الصالة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getEventTypes()
    {
        try {
            $eventTypes = EventType::all();

            if ($eventTypes->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا توجد أنواع أحداث متاحة حالياً.',
                    'events' => [],
                ], 200);
            }
            return response()->json([
                'status' => true,
                'message' => 'تم جلب أنواع الأحداث بنجاح.',
                'events' => $eventTypes,
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
     public function getHallsByEvent($eventTypeId)
    {
        try {
            $eventExists = EventType::where('id', $eventTypeId)->exists();
            if (!$eventExists) {
                return response()->json([
                    'status' => false,
                    'message' => 'نوع الحدث المحدد غير موجود.',
                ], 404);
            }

            $halls = Hall::where('event_type_id', $eventTypeId)
                ->select('id', 'name_ar', 'name_en', 'location_ar',
                 'location_en', 'capacity', 'price', 'image_1', 'image_2', 'image_3')
                ->get();

            if ($halls->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا توجد صالات لهذا النوع من الأحداث حالياً.',
                    'halls' => [],
                ], 200);
            }

            return response()->json([
                'status' => true,
                'message' => 'تم جلب الصالات بنجاح لنوع الحدث المحدد.',
                'halls' => $halls,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching halls by event type: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب الصالات حسب نوع الحدث.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
public function getServicesByHall($hallId)
        {
            try {
                $hall = Hall::with(['services' => function ($query) {
                    $query->select('services.id', 'name_ar', 'name_en');
                }])->find($hallId);

                if (!$hall) {
                    return response()->json([
                        'status' => false,
                        'message' => 'الصالة المطلوبة غير موجودة.',
                    ], 404);
                }

                if ($hall->services->isEmpty()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'لا توجد خدمات متاحة لهذه الصالة حالياً.',
                        'services' => [],
                    ], 200);
                }

                // تنسيق الخدمات لإرجاع البيانات المطلوبة فقط
                $formattedServices = $hall->services->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'name_ar' => $service->name_ar,
                        'name_en' => $service->name_en,
                        // لا يوجد 'price' أو 'image' هنا
                    ];
                });

                return response()->json([
                    'status' => true,
                    'message' => 'تم جلب خدمات الصالة بنجاح.',
                    'services' => $formattedServices,
                ], 200);

            } catch (\Exception $e) {
                Log::error('Error fetching services by hall: ' . $e->getMessage());
                return response()->json([
                    'status' => false,
                    'message' => 'حدث خطأ أثناء جلب خدمات الصالة.',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }
    public function getVariantsByService($serviceId)
    {
        try {
            $service = Service::with(['variants' => function ($query) {
                $query->select('id', 'service_id', 'name_ar', 'name_en', 'color', 'price');
            }])->find($serviceId);
            if (!$service) {
                return response()->json([
                    'status' => false,
                    'message' => 'الخدمة المطلوبة غير موجودة.',
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
            Log::error('Error fetching variants by service: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب أنواع الخدمة.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
