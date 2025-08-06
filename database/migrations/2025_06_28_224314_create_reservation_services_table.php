
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reservation_services', function (Blueprint $table) {
    $table->id();
    $table->foreignId('reservation_id')->constrained()->onDelete('cascade');
    $table->foreignId('service_id')->constrained()->onDelete('cascade');
    $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->onDelete('set null');
    $table->integer('quantity');
    $table->decimal('unit_price', 10, 2);
        $table->foreignId('coordinator_id')->constrained()->onDelete('cascade');


    $table->timestamps();

});

    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_services');
    }
};
