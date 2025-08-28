<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('notifications', function (Blueprint $table) {
        $table->uuid('id')->primary();

        // إذا users.id هو bigIncrements:
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        // إذا users.id هو uuid، استبدل السطر أعلاه بـ:
        // $table->uuid('user_id');
        // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

        $table->string('type')->nullable();
        // اجعل notifiable nullable وادعم uuid أو bigInt عن طريق جعله نص (أبسط وأعمّ حالاً)
        $table->string('notifiable_id')->nullable();
        $table->string('notifiable_type')->nullable();

        $table->string('title')->nullable();
        $table->json('data')->nullable();
        $table->timestamp('read_at')->nullable();
        $table->boolean('is_sent_to_firebase')->default(false);
        $table->timestamps();
    });
}


public function down(): void
{
    Schema::dropIfExists('notifications');
}

};
