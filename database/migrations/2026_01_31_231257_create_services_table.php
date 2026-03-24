<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();

            // Jenis servis: 'cuci', 'perbaikan', 'instalasi'
            $table->enum('jenis', ['cuci', 'perbaikan', 'installasi'])->default('perbaikan');

            // Status yang lebih lengkap
            $table->enum('status', [
                'menunggu_konfirmasi',  // Menunggu konfirmasi owner
                'ditugaskan',            // Sudah ditugaskan ke teknisi
                'dikerjakan',            // Sudah ditugaskan ke teknisi
                // 'dalam_perjalanan',      // Teknisi dalam perjalanan
                // 'dalam_pengerjaan',      // Sedang dikerjakan
                // 'menunggu_konfirmasi_owner', // Menunggu konfirmasi owner setelah pengerjaan
                'selesai',               // Sudah selesai
                'batal'                  // Dibatalkan
            ])->default('menunggu_konfirmasi');

            // Foreign keys
            $table->foreignId('complaint_id')->nullable()->constrained('complaints')->nullOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('ac_unit_id')->nullable()->constrained('ac_units')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();

            // Fields khusus untuk cuci (bisa multi AC)
            $table->json('ac_units')->nullable(); // Untuk pencucian multi AC: [ac_unit_id1, ac_unit_id2, ...]
            $table->integer('jumlah_ac')->default(1); // Jumlah AC yang akan dicuci

            // Detail servis
            $table->json('tindakan')->nullable(); // ["pembersihan","isi_freon",...]
            $table->text('diagnosa')->nullable();
            $table->text('catatan')->nullable();

            // Fields untuk request dari client (khusus perbaikan)
            $table->text('keluhan_client')->nullable(); // Keluhan dari client
            $table->json('foto_keluhan')->nullable(); // Foto kondisi AC dari client

            // Fields untuk pengerjaan teknisi
            $table->json('foto_sebelum')->nullable();
            $table->json('foto_pengerjaan')->nullable();
            $table->json('foto_sesudah')->nullable();
            $table->json('foto_suku_cadang')->nullable();

            // Timeline
            $table->dateTime('tanggal_berkunjung')->nullable();
            $table->dateTime('tanggal_ditugaskan')->nullable();
            $table->dateTime('tanggal_mulai')->nullable();
            $table->dateTime('tanggal_selesai')->nullable();
            $table->dateTime('tanggal_dikonfirmasi_owner')->nullable();
            $table->dateTime('tanggal_dikonfirmasi_client')->nullable();

            // Biaya
            $table->double('biaya_servis')->default(0);
            $table->double('biaya_suku_cadang')->default(0);
            $table->double('total_biaya')->default(0);

            // Invoice
            $table->string('no_invoice')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
