<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservation', function (Blueprint $table) {
            $table->id(); // رقم تعريف فريد للحجز

            // ربط الحجز بالمستخدم الذي قام بالحجز
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); 
            
            // ربط الحجز بالصالة المحجوزة
            $table->foreignId('hall_id')->nullable()->constrained()->onDelete('cascade'); 
            
            // ربط الحجز بنوع الحدث (زفاف، عيد ميلاد، إلخ)
            $table->foreignId('event_type_id')->constrained('event_types')->onDelete('cascade'); 
            
            $table->date('reservation_date'); // تاريخ الحجز
            $table->time('start_time')->nullable(); // وقت بداية الحجز (يمكن أن يكون فارغاً)
            $table->time('end_time')->nullable(); // وقت نهاية الحجز (يمكن أن يكون فارغاً)
            $table->string('home_address')->nullable(); // عنوان المنزل للعميل (يمكن أن يكون فارغاً)
            
            // حالة الحجز: معلق، ملغى، مؤكد
            $table->enum('status', ['pending','cancelled','confirmed'])->default('pending'); 
            
            $table->decimal('total_price', 12, 2)->default(0); // السعر الإجمالي للحجز
            $table->decimal('discount_amount', 10, 2)->default(0); // مبلغ الخصم المطبق

            // ربط الحجز بكود الخصم (يمكن أن يكون فارغاً)
            $table->foreignId('discount_code_id')->nullable()->constrained('discount_codes')->onDelete('set null'); 
            
            // ربط الحجز بالمنسق الرئيسي له (يمكن أن يكون فارغاً)
            // المنسقون هم 'users' وليس 'coordinators'
            $table->foreignId('coordinator_id')->nullable() 
                    ->constrained('users') // **هذا هو التصحيح الأهم: يشير إلى جدول 'users'**
                    ->onDelete('set null');
            
            $table->timestamps(); // أعمدة 'created_at' و 'updated_at' لتتبع الوقت
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // حذف جدول 'reservation' عند التراجع عن الترحيل
        Schema::dropIfExists('reservation');
    }
};