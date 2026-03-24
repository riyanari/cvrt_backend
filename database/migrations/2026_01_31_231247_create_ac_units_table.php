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
        Schema::create('ac_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('brand')->default('Unknown');
            $table->string('type')->default('Standard');
            $table->string('capacity')->default('1 PK');
            $table->dateTime('last_service')->nullable();
            $table->timestamps();

            $table->index(['room_id']);
            // $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            // $table->string('name');
            // $table->integer('lantai');
            // $table->string('brand')->default('Unknown');
            // $table->string('type')->default('Standard');
            // $table->string('capacity')->default('1 PK');
            // $table->dateTime('last_service')->nullable();
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ac_units');
    }
};
