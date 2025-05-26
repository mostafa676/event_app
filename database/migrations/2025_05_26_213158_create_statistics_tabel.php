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
        Schema::create('statistics', function (Blueprint $table) { 
            $table->id();
            $table->foreignId('hall_id')->nullable()->constrained(); // للإحصائيات الخاصة بالصالة
            $table->foreignId('user_id')->nullable()->constrained(); // للإحصائيات الخاصة بصاحب الصالة
            $table->string('metric_type'); // popular_hall, completed_reservations
            $table->integer('count')->default(0);
            $table->date('record_date');
            $table->timestamps();
            $table->index(['metric_type', 'record_date']); // لتحسين استعلامات التقارير
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statistics_tabel');
    }
};
