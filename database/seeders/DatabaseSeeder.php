<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Hall;
use App\Models\User;
use App\Models\PlaceType;
use App\Models\EventType;
use Illuminate\Support\Facades\Hash;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

                // إنشاء مستخدم مالك صالة
        User::firstOrCreate([
            'name' => 'Hall Owner',
            'email' => 'hallowner@example.com',
            'password' => Hash::make('12345678'), // كلمة المرور هذه ستكون 'password'
            'role' => 'hall_owner',
            'phone' => '0501234567',
        ]);
                // إنشاء مستخدم مدير
        User::firstOrCreate([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('87654321'),
            'role' => 'admin',
            'phone' => '0509876543',
        ]);

        PlaceType::firstOrCreate(['id' => 1], ['name_ar' => 'قاعة احتفالات', 'name_en' => 'Event Hall']);
        PlaceType::firstOrCreate(['id' => 2], ['name_ar' => 'مساحة خارجية', 'name_en' => 'Outdoor Space']);

        EventType::firstOrCreate(['id' => 1], ['name_ar' => 'زفاف', 'name_en' => 'Wedding']);
        EventType::firstOrCreate(['id' => 2], ['name_ar' => 'حفلة عيد ميلاد', 'name_en' => 'Birthday Party']);

        $hallOwner = User::where('email', 'hallowner@example.com')->first();

        if ($hallOwner) {
            Hall::firstOrCreate(
           ['name_ar' => 'قاعة الفخامة', 'user_id' => $hallOwner->id], // **تصحيح: استخدام 'user_id' بدلاً من 'hall_owner_id'**
                [
                    'name_en' => 'Grand Event Hall',
                    'location_ar' => 'موقع القاعة الفخمة',
                    'location_en' => 'Grand Hall Location',
                    'capacity' => 500,
                    'price' => 2500.00, // تأكدي من أن price هو decimal
                    'image_1' => null,
                    'image_2' => null,
                    'image_3' => null,
                    'place_type_id' => 1,
                    'event_type_id' => 1,
                ]
            );

    }
}
}