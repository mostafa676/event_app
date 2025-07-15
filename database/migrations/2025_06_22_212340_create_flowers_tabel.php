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
        Schema::create('flowers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('decoration_type_id')->constrained()->onDelete('cascade');
    $table->string('name_ar');
    $table->string('name_en');
    $table->string('color');
    $table->decimal('price', 10, 2);
    $table->string('image');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flowers');
    }
};
