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
    Schema::create('hall_ratings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('hall_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->tinyInteger('stars')->unsigned(); // من 1 إلى 5
        $table->text('review')->nullable(); // مراجعة نصية اختيارية
        $table->timestamps();
        $table->unique(['hall_id', 'user_id']); // يمنع تكرار التقييم من نفس المستخدم
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hall_ratings');
    }
};
