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
        // hapus foreign key rooms.floor_id lama kalau ada
        Schema::table('rooms', function (Blueprint $table) {
            try {
                $table->dropForeign(['floor_id']);
            } catch (\Throwable $e) {
            }
        });

        Schema::rename('floors', 'floors_old');
        Schema::rename('floor_masters', 'floors');

        Schema::table('rooms', function (Blueprint $table) {
            $table->foreign('floor_id')
                ->references('id')
                ->on('floors')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            try {
                $table->dropForeign(['floor_id']);
            } catch (\Throwable $e) {
            }
        });

        Schema::rename('floors', 'floor_masters');
        Schema::rename('floors_old', 'floors');

        Schema::table('rooms', function (Blueprint $table) {
            $table->foreign('floor_id')
                ->references('id')
                ->on('floors')
                ->restrictOnDelete();
        });
    }
};
