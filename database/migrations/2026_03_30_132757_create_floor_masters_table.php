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
        Schema::create('floor_masters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('number')->unique();
            $table->timestamps();
        });

        DB::statement("
            INSERT INTO floor_masters (name, number, created_at, updated_at)
            SELECT
                CONCAT('Lantai ', floor_number),
                floor_number,
                NOW(),
                NOW()
            FROM rooms
            WHERE floor_number IS NOT NULL
            GROUP BY floor_number
            ORDER BY floor_number
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('floor_masters');
    }
};
