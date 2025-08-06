<?php

namespace App\Http\Controllers\User; 

use App\Http\Controllers\Controller;
use App\Models\Coordinator;
use App\Models\CustomSongRequest;
use App\Models\DecorationType;
use App\Models\Flower;
use App\Models\FlowerPlacement;
use App\Models\Hall;
use App\Models\EventType;
use App\Models\HallRating;
use App\Models\PlaceType;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Models\ServiceVariant;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException; 

class HallController extends Controller
{

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
    
    public function getPlaceTypes()
{
    try {
        $types = PlaceType::all();

        return response()->json([
            'status' => true,
            'message' => 'تم جلب أنواع الأماكن بنجاح.',
            'data' => $types,
        ]);
    } catch (\Exception $e) {
        Log::error('Error fetching place types: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء جلب أنواع الأماكن.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function getHallsByPlaceType($placeTypeId)
{
    try {
        $placeType = PlaceType::with(['halls' => function($query) {
            $query->with('eventType', 'services'); // optional: eager load relations
        }])->find($placeTypeId);

        if (!$placeType) {
            return response()->json([
                'status' => false,
                'message' => 'نوع المكان غير موجود.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم جلب الصالات بنجاح حسب نوع المكان.',
            'place_type' => $placeType->name_ar,
            'halls' => $placeType->halls,
        ]);
    } catch (\Exception $e) {
        \Log::error('Error fetching halls by place type: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء جلب الصالات.',
            'error' => $e->getMessage()
        ], 500);
    }
}

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

public function getServiceCategories($serviceId)
{
    try {
        $categories = ServiceCategory::where('service_id', $serviceId)->get();

        if ($categories->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'لا توجد تصنيفات لهذه الخدمة.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم جلب التصنيفات بنجاح.',
            'categories' => $categories
        ]);
    } catch (\Exception $e) {
        \Log::error('Error fetching categories: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء جلب التصنيفات.',
            'error' => $e->getMessage()
        ], 500);
    }
}
public function getCategoryVariants($categoryId)
{
    try {
        $variants = ServiceVariant::where('service_category_id', $categoryId)->get();

        if ($variants->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'لا توجد أنواع لهذا التصنيف.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم جلب الأنواع بنجاح.',
            'variants' => $variants
        ]);
    } catch (\Exception $e) {
        \Log::error('Error fetching variants: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء جلب الأنواع.',
            'error' => $e->getMessage()
        ], 500);
    }
}
public function getVariantTypes($variantId)
{
    try {
        $types = ServiceType::where('service_variant_id', $variantId)->get();

        if ($types->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'لا توجد أصناف لهذا النوع.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم جلب الأصناف بنجاح.',
            'types' => $types
        ]);
    } catch (\Exception $e) {
        \Log::error('Error fetching types: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء جلب الأصناف.',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function getMusicServiceDetails($hallOwnerId)
    {
        try {
            $djs = Coordinator::where('hall_owner_id', $hallOwnerId)
                ->whereHas('type', fn($q) => $q->where('name_en', 'dj'))
                ->with('user', 'portfolios')
                ->get();

            $songs_ar = Song::where('language', 'ar')->get();
            $songs_en = Song::where('language', 'en')->get();

            return response()->json([
                'status' => true,
                'message' => 'تم جلب تفاصيل خدمة الموسيقى بنجاح.',
                'djs' => $djs,
                'songs_ar' => $songs_ar,
                'songs_en' => $songs_en,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching music service: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب خدمة الموسيقى.',
                'error' => $e->getMessage(),
            ], 500);
        }
}

public function requestCustomSong(Request $request)
{
    $request->validate([
        'reservation_id' => 'required|exists:reservation,id',
        'title' => 'required|string',
        'artist' => 'nullable|string',
    ]);

    try {
        $custom = CustomSongRequest::create($request->only('reservation_id', 'title', 'artist'));

        return response()->json([
            'status' => true,
            'message' => 'تم إرسال طلب الأغنية المخصصة بنجاح.',
            'song_request' => $custom,
        ]);
    } catch (\Exception $e) {
        Log::error('Error requesting custom song: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء إرسال طلب الأغنية.',
            'error' => $e->getMessage()
        ], 500);
    }
}
 
public function getPhotographers($hallOwnerId)
{
    try {
        $photographers = Coordinator::with('user', 'portfolios')
            ->where('hall_owner_id', $hallOwnerId)
            ->whereHas('type', fn($q) => $q->where('name_en', 'photographer'))
            ->get();
 if (!$photographers){
                return response()->json([
                    'status' => false,
                    'message' => 'خدمة  التصوير غير متوفرة.',
                ], 404);
            }
        return response()->json([
            'status' => true,
            'message' => 'تم جلب قائمة المصورين بنجاح.',
            'photographers' => $photographers,
        ]);
    } catch (\Exception $e) {
        Log::error('Error fetching photographers: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء جلب المصورين.',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function getDecorationTypes()
{
    try {
        $types = DecorationType::all();
        return response()->json([
            'status' => true,
            'message' => 'تم جلب أنواع الزينة بنجاح.',
            'decoration_types' => $types,
        ]);
    } catch (\Exception $e) {
        Log::error('Error fetching decoration types: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء جلب أنواع الزينة.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function getFlowersByDecorationType($decorationTypeId)
{
    try {
        $flowers = Flower::where('decoration_type_id', $decorationTypeId)
        ->get();

    return response()->json([
        'status' => true,
        'flowers' => $flowers,
    ]);
    } catch (\Exception $e) {
        Log::error('Error fetching decoration types: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء جلب أنواع الزينة.',
            'error' => $e->getMessage(),
        ], 500);
    }   
}

public function rateHall(Request $request, $hallId)
{
    $request->validate([
        'stars' => 'required|integer|min:1|max:5',
        'review' => 'nullable|string',
    ]);

    $user = auth()->user();

    $rating = HallRating::updateOrCreate(
        ['hall_id' => $hallId, 'user_id' => $user->id],
        ['stars' => $request->stars, 'review' => $request->review]
    );

    // تحديث متوسط التقييم في جدول halls
    $hall = Hall::with('ratings')->findOrFail($hallId);

    $average = $hall->ratings()->avg('stars');
    $count = $hall->ratings()->count();

    $hall->update([
        'average_rating' => $average,
        'rating_count' => $count,
    ]);

    return response()->json([
        'status' => true,
        'message' => 'تم حفظ التقييم بنجاح.',
        'rating' => $rating,
        'average_rating' => $average,
    ]);
}

public function getAvailableTimes($hallId)
{
    $hall = Hall::find($hallId);

    if (!$hall) {
        return response()->json([
            'status' => false,
            'message' => 'القاعة غير موجودة.',
        ], 404);
    }

    $schedules = $hall->schedules()
        ->where('is_available', true)
        ->get();

    // جلب الأوقات المحجوزة من جدول reservations
    $reservedTimes = $hall->reservations()
        ->select('reservation_date', 'start_time', 'end_time')
        ->get();

    // تصفية الأوقات المتاحة بناءً على المحجوزة
    $available = $schedules->filter(function ($slot) use ($reservedTimes) {
        foreach ($reservedTimes as $reserved) {
            // تحويل التاريخ إلى اسم يوم مثل Monday
            $reservedDay = \Carbon\Carbon::parse($reserved->reservation_date)->format('l');

            if (
                $reservedDay === $slot->day_of_week &&
                $reserved->start_time == $slot->start_time &&
                $reserved->end_time == $slot->end_time
            ) {
                return false; // هذا التوقيت محجوز
            }
        }
        return true; // هذا التوقيت متاح
    })->values(); // reset index

    return response()->json([
        'status' => true,
        'message' => 'تم جلب الأوقات المتاحة بنجاح.',
        'data' => $available,
    ]);
}

 
}
