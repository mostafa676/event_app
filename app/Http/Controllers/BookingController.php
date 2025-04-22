<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingServiceDetail;
use App\Models\Hall;
use App\Models\ServiceVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{

    public function createBooking(Request $request)
{
    $validated = $request->validate([
        'hall_id' => 'required|exists:halls,id',
        'event_type_id' => 'required|exists:event_types,id',
        'event_date' => 'required|date',
        'services' => 'required|array',
        'services.*.variant_id' => 'required|exists:service_variants,id',
        'services.*.quantity' => 'required|integer|min:1',
    ]);

    DB::beginTransaction();

    try {
        $totalPrice = 0;

        // حساب سعر الخدمات المختارة
        $serviceDetails = [];
        foreach ($validated['services'] as $service) {
            $variant = ServiceVariant::findOrFail($service['variant_id']);
            $price = $variant->price * $service['quantity'];
            $totalPrice += $price;

            $serviceDetails[] = [
                'variant_id' => $variant->id,
                'quantity' => $service['quantity'],
                'total_price' => $price,
            ];
        }

        // إضافة سعر الصالة
        $hallPrice = Hall::findOrFail($validated['hall_id'])->price;
        $totalPrice += $hallPrice;

        // إنشاء الحجز
        $booking = Booking::create([
            'user_id' => Auth::id(),
            'hall_id' => $validated['hall_id'],
            'event_type_id' => $validated['event_type_id'],
            'event_date' => $validated['event_date'],
            'total_price' => $totalPrice,
        ]);

        // حفظ تفاصيل الخدمات
        foreach ($serviceDetails as $detail) {
            BookingServiceDetail::create([
                'booking_id' => $booking->id,
                'variant_id' => $detail['variant_id'],
                'quantity' => $detail['quantity'],
                'total_price' => $detail['total_price'],
            ]);
        }

        DB::commit();

        return response()->json([
            'message' => 'Booking created successfully',
            'booking_id' => $booking->id
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Failed to create booking',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function getUserBookings(Request $request)
{
    $user = $request->user(); // المستخدم المسجّل حالياً (من التوكن)

    $bookings = Booking::with(['hall', 'eventType'])
        ->where('user_id', $user->id)
        ->orderBy('event_date', 'desc')
        ->get();

    return response()->json([
        'message' => 'User bookings retrieved successfully',
        'bookings' => $bookings
    ], 200);
}

public function getBookingDetails($bookingId)
{
    $booking = Booking::with([
        'hall',
        'eventType',
        'serviceDetails.serviceVariant.service'
    ])->find($bookingId);

    if (!$booking) {
        return response()->json([
            'message' => 'Booking not found'
        ], 404);
    }

    return response()->json([
        'message' => 'Booking details retrieved successfully',
        'booking' => $booking
    ], 200);
}

}
