<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartServiceItem;
use App\Models\Reservation;
use App\Models\ServiceVariant;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function confirmReservation(Request $request)
{
    $request->validate([
        'reservation_date' => 'required|date',
        'note' => 'nullable|string',
    ]);

    $cart = Cart::with('services')->where('user_id', auth()->id())->firstOrFail();

    $reservation = Reservation::create([
        'user_id' => auth()->id(),
        'reservation_date' => $request->reservation_date,
        'hall_id' => $cart->hall_id,
        'event_id' => $cart->event_type_id,
        'note' => $request->note,
        'status' => 'pending',
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

    return response()->json(['message' => 'تم تأكيد الحجز بنجاح', 'reservation' => $reservation]);
}

    

public function getCart()
    {
        $cart = Cart::with('services.variant.service', 'hall', 'event')
            ->firstOrCreate(['user_id' => auth()->id()]);

        return response()->json($cart);
    }

    // 2. اختيار نوع الحدث (مرة واحدة عند بداية الحجز)
    public function selectEventType(Request $request)
    {
        $request->validate(['event_type_id' => 'required|exists:event_types,id']);

        $cart = Cart::firstOrCreate(['user_id' => auth()->id()]);
        $cart->update(['event_type_id' => $request->event_type_id]);

        return response()->json(['message' => 'تم اختيار نوع الحدث بنجاح', 'cart' => $cart]);
    }

    // 3. اختيار الصالة
    public function selectHall(Request $request)
    {
        $request->validate(['hall_id' => 'required|exists:halls,id']);

        $cart = Cart::firstOrCreate(['user_id' => auth()->id()]);
        $cart->update(['hall_id' => $request->hall_id]);

        return response()->json(['message' => 'تم اختيار الصالة بنجاح', 'cart' => $cart]);
    }

    // 4. إضافة خدمة للسلة
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

    // 5. حذف خدمة من السلة
    public function removeService($id)
    {
        $item = CartServiceItem::where('id', $id)
                ->whereHas('cart', fn($q) => $q->where('user_id', auth()->id()))
                ->firstOrFail();

        $item->delete();

        return response()->json(['message' => 'تم حذف الخدمة من السلة']);
    }

    // 6. حذف السلة بالكامل (مثلاً إذا غادر المستخدم)
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
