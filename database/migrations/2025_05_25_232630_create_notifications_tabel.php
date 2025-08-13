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
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('type');
        $table->morphs('notifiable');
        $table->string('title')->nullable(); // عنوان مختصر
        $table->text('data'); // JSON
        $table->timestamp('read_at')->nullable();
        $table->boolean('is_sent_to_firebase')->default(false); // فحص إذا أُرسل
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('notifications');
}

};
