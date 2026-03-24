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
        Schema::create('service_technicians', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_id')
                ->constrained('services')
                ->cascadeOnDelete();

            $table->foreignId('technician_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // opsional (tapi berguna)
            $table->boolean('is_lead')->default(false);
            $table->timestamp('assigned_at')->nullable();

            $table->timestamps();

            $table->unique(['service_id', 'technician_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_technicians');
    }
};
