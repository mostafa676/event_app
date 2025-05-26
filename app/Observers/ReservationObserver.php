<?php

namespace App\Observers;

use App\Models\Reservation;
use App\Models\Statistic;

class ReservationObserver
{
    public function completed(Reservation $reservation)
    {
        // تحديث إحصائية الصالة الأكثر طلباً
        Statistic::updateOrCreate(
            [
                'hall_id' => $reservation->hall_id,
                'metric_type' => 'popular_hall',
                'record_date' => now()->format('Y-m-d')
            ],
            ['count' => \DB::raw('count + 1')]
        );

        // تحديث إحصائية الحجوزات المكتملة لصاحب الصالة
        Statistic::updateOrCreate(
            [
                'user_id' => $reservation->hall->owner->id,
                'metric_type' => 'completed_reservations',
                'record_date' => now()->format('Y-m-d')
            ],
            ['count' => \DB::raw('count + 1')]
        );
    }
}