<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_id')
                ->constrained('services')
                ->cascadeOnDelete();

            $table->foreignId('ac_unit_id')
                ->constrained('ac_units')
                ->cascadeOnDelete();

            // teknisi per AC
            $table->foreignId('technician_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('assigned_at')->nullable();

            // status per AC
            $table->string('status')->default('menunggu_konfirmasi');

            // timeline per item (per AC)
            $table->dateTime('tanggal_berkunjung')->nullable();
            $table->dateTime('tanggal_mulai')->nullable();
            $table->dateTime('tanggal_selesai')->nullable();
            $table->dateTime('tanggal_dikonfirmasi_owner')->nullable();
            $table->dateTime('tanggal_dikonfirmasi_client')->nullable();

            // foto per item (per AC)
            $table->json('foto_sebelum')->nullable();
            $table->json('foto_pengerjaan')->nullable();
            $table->json('foto_sesudah')->nullable();
            $table->json('foto_suku_cadang')->nullable();

            // detail per item (kalau memang kamu pindahkan per AC)
            $table->text('diagnosa')->nullable();
            $table->longText('tindakan')->nullable(); 
            $table->text('catatan')->nullable();

            $table->timestamps();

            // 1 service hanya boleh punya 1 item untuk 1 ac_unit
            $table->unique(['service_id', 'ac_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_items');
    }
};
