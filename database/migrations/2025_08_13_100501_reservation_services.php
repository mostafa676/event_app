<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reservation_services', function (Blueprint $table) {
            $table->string('song_id')->nullable()
    ->constrained('songs')
    ->onDelete('set null');
            $table->string('custom_song_title')->nullable()->after('song_id');
            $table->string('custom_song_artist')->nullable()->after('custom_song_title');
            
        });
    }

    public function down(): void
    {
        Schema::table('reservation_services', function (Blueprint $table) {
            $table->dropColumn(['custom_song_title', 'custom_song_artist']);
        });
    }
};
