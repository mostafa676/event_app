<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('hall_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('event_type_id')->constrained('event_types')->onDelete('cascade');
            $table->date('reservation_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();  
            $table->string('home_address')->nullable();
            $table->enum('status', ['pending','cancelled','confirmed'])->default('pending');
            $table->decimal('total_price', 12, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->foreignId('coordinator_id')->nullable() // اجعله nullable لأن الحجز قد لا يكون له منسق رئيسي في البداية
                  ->constrained('coordinators') // يربط بجدول 'coordinators'
                  ->onDelete('set null') // إذا تم حذف المنسق، يتم تعيين القيمة إلى NULL
                  ->after('discount_code_id'); // يمكنك تغيير هذا لتحديد موقعه
                  $table->foreignId('discount_code_id')->nullable()->constrained('discount_codes')->onDelete('set null'); // ربط بكود الخصم
            $table->timestamps();
        });
        
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation');
    }
};
    