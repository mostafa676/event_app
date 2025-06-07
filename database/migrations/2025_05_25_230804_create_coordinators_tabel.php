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
        Schema::create('coordinator_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->timestamps();
});
        Schema::create('coordinators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('coordinator_type_id')->constrained();
            $table->foreignId('hall_owner_id')->constrained('users')->onDelete('cascade');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true); 
            $table->decimal('hourly_rate', 8, 2)->nullable(); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coordinators');
        Schema::dropIfExists('coordinator_types');
    }
};
