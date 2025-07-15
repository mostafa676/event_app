<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Reservation; // تأكدي من استيراد هذا النموذج
use App\Models\CoordinatorAssignment; // تأكدي من استيراد هذا النموذج
use App\Models\Service;
use App\Models\Hall;
use App\Models\EventType;
use App\Models\PlaceType;
use App\Models\DiscountCode; // تأكدي من استيراد هذا النموذج
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1. إنشاء مستخدمين (Users)
        // يتم إنشاء المستخدمين عبر UserFactory لأنها Factory الوحيدة المطلوبة
        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password1'),
        ]);

        $hallOwner = User::factory()->hallOwner()->create([
            'name' => 'Hall Owner',
            'email' => 'hallowner@example.com',
            'password' => Hash::make('password2'),
        ]);

        $coordinator1 = User::factory()->coordinator()->create([
            'name' => 'Coordinator One',
            'email' => 'coordinator1@example.com',
            'password' => Hash::make('password11'),
        ]);

        $coordinator2 = User::factory()->coordinator()->create([
            'name' => 'Coordinator Two',
            'email' => 'coordinator2@example.com',
            'password' => Hash::make('password12'),
        ]);

        $regularUser = User::factory()->regularUser()->create([
            'name' => 'Regular Customer',
            'email' => 'customer@example.com',
            'password' => Hash::make('password22'),
        ]);

        // 2. إنشاء الخدمات (Services) - بيانات مباشرة
        $serviceFood = Service::firstOrCreate(['name_en' => 'Food Service'], [
            'name_ar' => 'خدمة الطعام',
        ]);

        $servicePhotography = Service::firstOrCreate(['name_en' => 'Photography Service'], [
            'name_ar' => 'خدمة التصوير',
        ]);

        $serviceMusic = Service::firstOrCreate(['name_en' => 'Music Service'], [
            'name_ar' => 'خدمة الموسيقى',
        ]);

        // 3. إنشاء EventType و PlaceType - بيانات مباشرة
        $eventType = EventType::firstOrCreate(['name_en' => 'Wedding'], [
            'name_ar' => 'زفاف',
            'image' => null,
        ]);

        $placeType = PlaceType::firstOrCreate(['name_en' => 'Ballroom'], [
            'name_ar' => 'قاعة احتفالات',
        ]);

        // 4. إنشاء صالة (Hall) - بيانات مباشرة
        $hall = Hall::firstOrCreate(['name_en' => 'Grand Ballroom'], [
            'name_ar' => 'القاعة الكبرى',
            'user_id' => $hallOwner->id,
            'location_ar' => 'مدينة الأحلام',
            'location_en' => 'Dream City',
            'capacity' => 200,
            'price' => 100.00,
            'image_1' => null,
            'image_2' => null,
            'image_3' => null,
            'event_type_id' => $eventType->id,
            'place_type_id' => $placeType->id,
        ]);

        // 5. إنشاء كود خصم (DiscountCode) - بيانات مباشرة
        $discountCode = DiscountCode::firstOrCreate(['code' => 'DISCOUNT10'], [
            'type' => 'percentage',
            'value' => 10.00,
            'valid_from' => now()->subDays(5)->toDateString(),
            'valid_to' => now()->addDays(30)->toDateString(),
            'max_uses' => 100,
            'is_active' => true,
        ]);

        // 6. إنشاء حجز تجريبي (Reservation) - بيانات مباشرة
        $reservation = Reservation::create([
            'user_id' => $regularUser->id,
            'hall_id' => $hall->id,
            'event_type_id' => $eventType->id,
            'reservation_date' => now()->addDays(10)->toDateString(),
            'start_time' => '18:00:00',
            'end_time' => '22:00:00',
            'home_address' => 'عنوان العميل التجريبي',
            'status' => 'confirmed',
            'total_price' => 2500.00,
            'discount_amount' => 0,
            'discount_code_id' => $discountCode->id,
            'coordinator_id' => $coordinator1->id,
        ]);

        // 7. إنشاء مهام منسق تجريبية (CoordinatorAssignment) - بيانات مباشرة
        CoordinatorAssignment::create([
            'reservation_id' => $reservation->id,
            'service_id' => $serviceFood->id,
            'coordinator_id' => $coordinator2->id,
            'assigned_by' => $hallOwner->id,
            'instructions' => 'الإشراف على جودة وتقديم الطعام في الحفل.',
            'status' => 'pending',
            'completed_at' => null,
        ]);

        CoordinatorAssignment::create([
            'reservation_id' => $reservation->id,
            'service_id' => $servicePhotography->id,
            'coordinator_id' => $coordinator1->id,
            'assigned_by' => $hallOwner->id,
            'instructions' => 'التأكد من تغطية جميع لحظات الحفل الهامة.',
            'status' => 'pending',
            'completed_at' => null,
        ]);

        CoordinatorAssignment::create([
            'reservation_id' => $reservation->id,
            'service_id' => null, // مهمة عامة بدون خدمة محددة
            'coordinator_id' => $coordinator2->id,
            'assigned_by' => $hallOwner->id,
            'instructions' => 'مراجعة الترتيبات النهائية للقاعة.',
            'status' => 'pending',
            'completed_at' => null,
        ]);
    }
}