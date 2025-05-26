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
            $table->foreignId('event_type_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('halls');
    }
};
