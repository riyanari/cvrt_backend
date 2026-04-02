<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ac_catalogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('ac_brands')->cascadeOnDelete();
            $table->foreignId('type_id')->constrained('ac_types')->cascadeOnDelete();
            $table->foreignId('capacity_id')->constrained('ac_capacities')->cascadeOnDelete();
            $table->string('series', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['brand_id', 'type_id', 'capacity_id', 'series'], 'ac_catalog_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ac_catalogs');
    }
};