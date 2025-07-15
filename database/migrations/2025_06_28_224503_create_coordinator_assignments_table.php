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
    Schema::create('coordinator_assignments', function (Blueprint $table) {
        $table->id();
        //ربط المهمة بالحجز 
        $table->unsignedBigInteger('reservation_id');
        //ربط المهمة بالخدمات
        $table->unsignedBigInteger('service_id')->nullable();
        $table->unsignedBigInteger('coordinator_id');
        $table->unsignedBigInteger('assigned_by');
        //حالة المهمة (معلقة ,مقبولة,مكتملة,مرفوضة)
        $table->enum('status', ['pending', 'accepted', 'completed', 'rejected'])->default('pending');
        $table->text('instructions')->nullable();
        $table->timestamp('completed_at')->nullable();
        $table->timestamps();

        // تعريف المفاتيح الخارجية بشكل منفصل
        $table->foreign('reservation_id')->references('id')->on('reservation')->onDelete('cascade');
        $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        $table->foreign('coordinator_id')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('assigned_by')->references('id')->on('users')->onDelete('cascade');
    });
}


    public function down(): void
    {
        Schema::dropIfExists('coordinator_assignments');
    }
};
