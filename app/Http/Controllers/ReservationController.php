<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartServiceItem;
use App\Models\Reservation;
use App\Models\ServiceVariant;
use App\Models\Supervisor;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
public function confirmReservation(Request $request)
{
    $request->validate([
        'reservation_date' => 'required|date',
        'start_time' => 'required|date_format:H:i', // لازم تبعت وقت بداية
        'end_time' => 'required|date_format:H:i',   // ووقت نهاية
        'home_address' => 'nullable|string|max:255',
    ]);
    $cart = Cart::with('services')->where('user_id', auth()->id())->firstOrFail();
    $reservationDate = $request->reservation_date;
    $startTime = $request->start_time;
    $endTime = $request->end_time;
    $supervisor = Supervisor::whereDoesntHave('reservations', function($query) use ($reservationDate, $startTime, $endTime) {
        $query->where('reservation_date', $reservationDate)
              ->where(function($q) use ($startTime, $endTime) {
                  $q->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function($q2) use ($startTime, $endTime) {
                        $q2->where('start_time', '<=', $startTime)
                           ->where('end_time', '>=', $endTime);
                    });
              });
    })
    ->withCount('reservations') // نحسب عدد الحجوزات عكل مشرف
    ->orderBy('reservations_count', 'asc') // ترتيب الأقل حجوزات
    ->first();
    if (!$supervisor) {
        return response()->json([
            'message' => 'لا يوجد مشرفين متاحين حالياً، الرجاء المحاولة لاحقاً.'
        ], 400);
    }
    $reservationData = [
        'user_id' => auth()->id(),
        'reservation_date' => $reservationDate,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'hall_id' => $cart->hall_id,
        'event_id' => $cart->event_type_id,
        'status' => 'confirmed',
        'supervisor_id' => $supervisor->id,
    ];
    if ($cart->hall_id == 9999 && $request->filled('home_address')) {
        $reservationData['home_address'] = $request->home_address;
    }
    $reservation = Reservation::create($reservationData);
    foreach ($cart->services as $item) {
        $reservation->services()->create([
            'service_id' => $item->service_id,
            'service_variant_id' => $item->service_variant_id,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
        ]);
    }
    $cart->services()->delete();
    $cart->delete();
    $reservation->load(['hall', 'event', 'services.service', 'services.variant', 'supervisor']);
    return response()->json([
        'message' => "تم تأكيد الحجز بنجاح. لأي استفسار، اتصل بالمشرف: {$supervisor->phone}",
        'reservation' => [
            'id' => $reservation->id,
            'reservation_date' => $reservation->reservation_date,
            'start_time' => $reservation->start_time,
            'end_time' => $reservation->end_time,
            'status' => $reservation->status,
            'hall' => [
                'id' => $reservation->hall->id ?? null,
                'name_ar' => $reservation->hall->name_ar ?? null,
                'name_en' => $reservation->hall->name_en ?? null,
                'price' => $reservation->hall->price ?? null,
            ],
            'event' => [
                'id' => $reservation->event->id ?? null,
                'name_ar' => $reservation->event->name_ar ?? null,
                'name_en' => $reservation->event->name_en ?? null,
            ],
            'home_address' => $reservation->home_address ?? null,
            'services' => $reservation->services,
            'supervisor' => [
                'id' => $supervisor->id,
                'name' => $supervisor->name,
                'phone' => $supervisor->phone,
            ]
        ]
    ], 201);
}
public function getCart()
{
    $cart = Cart::with('services.variant.service', 'hall', 'event')
        ->firstOrCreate(['user_id' => auth()->id()]);
    $totalPrice = 0;
    if ($cart->hall) {
        $totalPrice += $cart->hall->price;
    }
    foreach ($cart->services as $item) {
        $totalPrice += $item->quantity * $item->unit_price;
    }
    $servicesDetails = $cart->services->map(function ($serviceItem) {
        return [
            'id' => $serviceItem->id,
            'service_name_ar' => $serviceItem->variant->service->name_ar ?? null,
            'service_name_en' => $serviceItem->variant->service->name_en ?? null,
            'variant_name_ar' => $serviceItem->variant->name_ar ?? null,
            'variant_name_en' => $serviceItem->variant->name_en ?? null,
            'quantity' => $serviceItem->quantity,
            'unit_price' => $serviceItem->unit_price,
            'total_price' => $serviceItem->quantity * $serviceItem->unit_price,
        ];
    });
    return response()->json([
        'cart_id' => $cart->id,
        'event' => $cart->event ? [
            'id' => $cart->event->id,
            'name_ar' => $cart->event->name_ar,
            'name_en' => $cart->event->name_en,
        ] : null,
        'hall' => $cart->hall ? [
            'id' => $cart->hall->id,
            'name_ar' => $cart->hall->name_ar,
            'name_en' => $cart->hall->name_en,
            'price' => $cart->hall->price,
        ] : null,
        'services' => $servicesDetails,
        'total_price' => $totalPrice,
    ]);
}

    public function selectEventType(Request $request)
    {
        $request->validate(['event_type_id' => 'required|exists:event_types,id']);
        $cart = Cart::firstOrCreate(['user_id' => auth()->id()]);
        $cart->update(['event_type_id' => $request->event_type_id]);
        return response()->json(['message' => 'تم اختيار نوع الحدث بنجاح', 'cart' => $cart]);
    }
    public function selectHall(Request $request)
{
    $request->validate([
        'hall_id' => 'required|exists:halls,id',
        'reservation_date' => 'required|date',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i|after:start_time',
    ]);
    $conflict = Reservation::where('hall_id', $request->hall_id)
        ->where('reservation_date', $request->reservation_date)
        ->where(function ($query) use ($request) {
            $query->where(function ($q) use ($request) {
                $q->where('start_time', '<', $request->end_time)
                  ->where('end_time', '>', $request->start_time);
            });
        })
        ->exists();
    if ($conflict) {
        return response()->json([
            'message' => 'عذراً، هذه الفترة الزمنية محجوزة بالفعل للصالة المطلوبة.'
        ], 400);
    }
    $cart = Cart::firstOrCreate(['user_id' => auth()->id()]);
    $cart->update([
        'hall_id' => $request->hall_id,
        'reservation_date' => $request->reservation_date,
        'start_time' => $request->start_time,
        'end_time' => $request->end_time,
    ]);
    return response()->json([
        'message' => 'تم اختيار الصالة بنجاح.',
        'cart' => $cart
    ]);
}

    public function addService(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'service_variant_id' => 'required|exists:service_variants,id',
            'quantity' => 'required|integer|min:1'
        ]);
        $variant = ServiceVariant::findOrFail($request->service_variant_id);
        $cart = Cart::firstOrCreate(['user_id' => auth()->id()]);
        $item = CartServiceItem::create([
            'cart_id' => $cart->id,
            'service_id' => $request->service_id,
            'service_variant_id' => $request->service_variant_id,
            'quantity' => $request->quantity,
            'unit_price' => $variant->price,
        ]);
        return response()->json(['message' => 'تمت إضافة الخدمة للسلة', 'item' => $item]);
    }
    public function removeService($id)
    {
        $item = CartServiceItem::where('id', $id)
                ->whereHas('cart', fn($q) => $q->where('user_id', auth()->id()))
                ->firstOrFail();
        $item->delete();
        return response()->json(['message' => 'تم حذف الخدمة من السلة']);
    }
    public function clearCart()
    {
        $cart = Cart::where('user_id', auth()->id())->first();
        if ($cart) {
            $cart->services()->delete();
            $cart->delete();
        }
        return response()->json(['message' => 'تم إفراغ السلة']);
    }

}
