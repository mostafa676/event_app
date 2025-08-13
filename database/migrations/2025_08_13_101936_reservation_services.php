<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reservation_services', function (Blueprint $table) {
            $table->string('location')->nullable()->after('unit_price');
            $table->string('color')->nullable()->after('location');
         });
    }

    public function down(): void
    {
        Schema::table('reservation_services', function (Blueprint $table) {
            $table->dropColumn(['location', 'color']);
        });
    }
};
