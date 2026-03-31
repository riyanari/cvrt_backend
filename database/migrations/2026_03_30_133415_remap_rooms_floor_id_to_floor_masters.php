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
        DB::statement("
            UPDATE rooms r
            JOIN floor_masters fm ON fm.number = r.floor_number
            SET r.floor_id = fm.id
        ");
    }

    public function down(): void
    {
        // tidak dirollback otomatis
    }
};
