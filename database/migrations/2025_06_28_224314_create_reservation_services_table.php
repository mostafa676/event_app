
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reservation_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservation')->onDelete('cascade');
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_variant_id')->constrained()->onDelete('cascade');
            $table->foreignId('coordinator_id')->nullable()->constrained('coordinators')->onDelete('set null');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->foreignId('song_id')->nullable()->constrained('songs')->onDelete('set null');
            $table->string('custom_song_title')->nullable();
            $table->string('custom_song_artist')->nullable();
            $table->string('location')->nullable();
            $table->string('color')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_services');
    }
};
