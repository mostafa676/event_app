<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartServiceItem;
use App\Models\Reservation;
use App\Models\ServiceVariant;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
//     public function confirmReservation(Request $request)
// {
//     $request->validate([
//         'reservation_date' => 'required|date',
//         'note' => 'nullable|string',
//     ]);

//     $cart = Cart::with('services')->where('user_id', auth()->id())->firstOrFail();

//     $reservation = Reservation::create([
//         'user_id' => auth()->id(),
//         'reservation_date' => $request->reservation_date,
//         'hall_id' => $cart->hall_id,
//         'event_id' => $cart->event_type_id,
//         'note' => $request->note,
//         'status' => 'pending',
//     ]);

//     foreach ($cart->services as $item) {
//         $reservation->services()->create([
//             'service_id' => $item->service_id,
//             'service_variant_id' => $item->service_variant_id,
//             'quantity' => $item->quantity,
//             'unit_price' => $item->unit_price,
//         ]);
//     }

//     // حذف السلة بعد التأكيد
//     $cart->services()->delete();
//     $cart->delete();

//     return response()->json(['message' => 'تم تأكيد الحجز بنجاح', 'reservation' => $reservation]);
// }

    
public function confirmReservation(Request $request)
{
    $request->validate([
        'reservation_date' => 'required|date',
    ]);

    $cart = Cart::with('services')->where('user_id', auth()->id())->firstOrFail();

    $reservation = Reservation::create([
        'user_id' => auth()->id(),
        'reservation_date' => $request->reservation_date,
        'hall_id' => $cart->hall_id,
        'event_id' => $cart->event_type_id,
        'status' => 'confirmed',
    ]);

    foreach ($cart->services as $item) {
        $reservation->services()->create([
            'service_id' => $item->service_id,
            'service_variant_id' => $item->service_variant_id,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
        ]);
    }

    // حذف السلة بعد التأكيد
    $cart->services()->delete();
    $cart->delete();

    // تجهيز تفاصيل الحجز للعرض
    $reservation->load(['hall', 'event', 'services.service', 'services.variant']);

    $servicesDetails = $reservation->services->map(function ($serviceItem) {
        return [
            'service_name_ar' => $serviceItem->service->name_ar ?? null,
            'service_name_en' => $serviceItem->service->name_en ?? null,
            'variant_name_ar' => $serviceItem->variant->name_ar ?? null,
            'variant_name_en' => $serviceItem->variant->name_en ?? null,
            'quantity' => $serviceItem->quantity,
            'unit_price' => $serviceItem->unit_price,
            'total_price' => $serviceItem->quantity * $serviceItem->unit_price,
        ];
    });

    return response()->json([
        'message' => 'تم تأكيد الحجز بنجاح',
        'reservation' => [
            'id' => $reservation->id,
            'reservation_date' => $reservation->reservation_date,
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
            'services' => $servicesDetails,
        ]
    ], 201);
}

public function getCart()
{
    $cart = Cart::with('services.variant.service', 'hall', 'event')
        ->firstOrCreate(['user_id' => auth()->id()]);

    // حساب السعر الإجمالي
    $totalPrice = 0;

    // إضافة سعر الصالة إذا موجودة
    if ($cart->hall) {
        $totalPrice += $cart->hall->price;
    }

    // حساب أسعار الخدمات
    foreach ($cart->services as $item) {
        $totalPrice += $item->quantity * $item->unit_price;
    }

    // تجهيز تفاصيل الخدمات
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
        $request->validate(['hall_id' => 'required|exists:halls,id']);

        $cart = Cart::firstOrCreate(['user_id' => auth()->id()]);
        $cart->update(['hall_id' => $request->hall_id]);

        return response()->json(['message' => 'تم اختيار الصالة بنجاح', 'cart' => $cart]);
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
