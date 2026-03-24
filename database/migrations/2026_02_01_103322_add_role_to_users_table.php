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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('client'); // owner|client|teknisi
            $table->string('phone')->nullable();
            $table->string('spesialisasi')->nullable(); // untuk teknisi
            $table->double('rating')->default(0);
            $table->integer('total_service')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'spesialisasi', 'rating', 'total_service']);
        });
    }
};
