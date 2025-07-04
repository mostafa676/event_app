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
        Schema::create('coordinator_portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coordinator_id')->constrained()->onDelete('cascade');
            $table->string('image');
            $table->string('camera_info')->nullable();
            $table->text('description')->nullable(); // نوع التصوير مثلاً: حفلات – بورتريه
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coordinator_portfolios');
    }
};
