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
        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->nullable()->after('id');
            $table->unsignedInteger('floor_number')->nullable()->after('location_id');
        });

        // pindahkan data dari floors ke rooms
        DB::statement("
            UPDATE rooms
            INNER JOIN floors ON floors.id = rooms.floor_id
            SET
                rooms.location_id = floors.location_id,
                rooms.floor_number = floors.number
        ");

        Schema::table('rooms', function (Blueprint $table) {
            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn(['location_id', 'floor_number']);
        });
    }
};
