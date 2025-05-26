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
Schema::create('notifications', function (Blueprint $table) {
    $table->uuid('id')->primary(); // معرف فريد من نوع UUID
    $table->foreignId('user_id')->constrained()->onDelete('cascade'); // المستخدم المستهدف
    $table->string('type'); // نوع الإشعار (مثال: 'reservation_confirmed')
    $table->morphs('notifiable'); // ينشئ عمودين:
        // notifiable_id: ID للعنصر المرتبط
        // notifiable_type: نوع العنصر (مثال: 'App\Models\Reservation')
    $table->text('data'); // بيانات الإشعار (تخزن كـ JSON)
    $table->timestamp('read_at')->nullable(); // وقت قراءة الإشعار
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications_tabel');
    }
};
