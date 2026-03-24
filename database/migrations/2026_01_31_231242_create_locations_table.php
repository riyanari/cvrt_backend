<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('name');
            $table->string('address');

            // ===== Google Maps fields =====
            // presisi cukup untuk koordinat (± centimeter-level)
            $table->decimal('latitude', 10, 7)->nullable();   // -90..90
            $table->decimal('longitude', 10, 7)->nullable();  // -180..180
            $table->string('place_id')->nullable();           // Google Places place_id (optional)
            $table->string('gmaps_url')->nullable();          // optional share URL

            $table->unsignedInteger('jumlah_ac')->default(0);
            $table->dateTime('last_service')->nullable();

            $table->timestamps();

            // Index biar pencarian map lebih cepat
            $table->index(['client_id']);
            $table->index(['latitude', 'longitude']);
            $table->index(['place_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
