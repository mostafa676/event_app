<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('halls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); 
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('location_ar');
            $table->string('location_en');
            $table->integer('capacity');
            $table->decimal('price', 10, 2);
            $table->string('image_1')->nullable();
            $table->string('image_2')->nullable();
            $table->string('image_3')->nullable();
            $table->string('image_4')->nullable();
            $table->string('image_5')->nullable();
            $table->string('image_6')->nullable();
            $table->foreignId('event_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('place_type_id')->constrained()->onDelete('cascade');
            $table->decimal('average_rating', 3, 2)->default(0); // مثال: 4.25
            $table->unsignedInteger('rating_count')->default(0); // عدد التقييمات
            $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('halls');
    }
};
