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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'hall_owner', 'coordinator', 'user'])->default('user');
            $table->string('image')->nullable();
            $table->boolean('dark_mode')->default(false); // للوضع الداكن
            $table->string('language')->default('ar'); // ar أو en
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
{
    Schema::dropIfExists('users');
}

};
