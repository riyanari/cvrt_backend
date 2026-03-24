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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ac_unit_id')->constrained('ac_units')->cascadeOnDelete();

            $table->string('title');
            $table->text('description');

            $table->string('status')->default('diajukan');   // diajukan/dikirim/diproses/selesai/ditolak
            $table->string('priority')->default('sedang');   // rendah/sedang/tinggi/darurat

            $table->dateTime('submitted_at');
            $table->dateTime('completed_at')->nullable();

            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // teknisi/servicer
            $table->text('servicer_notes')->nullable();

            $table->json('foto_keluhan')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
