<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1️⃣ Tambah kolom room_id dulu (nullable)
        Schema::table('ac_units', function (Blueprint $table) {
            $table->foreignId('room_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();

            $table->index('room_id');
        });

        DB::transaction(function () {

            // 2️⃣ Buat floors dari kombinasi location_id + lantai
            $pairs = DB::table('ac_units')
                ->select('location_id', 'lantai')
                ->whereNotNull('location_id')
                ->whereNotNull('lantai')
                ->distinct()
                ->get();

            foreach ($pairs as $p) {
                DB::table('floors')->updateOrInsert(
                    [
                        'location_id' => $p->location_id,
                        'number' => (int) $p->lantai,
                    ],
                    [
                        'name' => 'Lantai ' . (int) $p->lantai,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            // 3️⃣ Buat rooms dan mapping AC
            $acUnits = DB::table('ac_units')->get();

            foreach ($acUnits as $ac) {

                $floorId = DB::table('floors')
                    ->where('location_id', $ac->location_id)
                    ->where('number', (int) $ac->lantai)
                    ->value('id');

                if (!$floorId) continue;

                DB::table('rooms')->updateOrInsert(
                    [
                        'floor_id' => $floorId,
                        'name' => $ac->name,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $roomId = DB::table('rooms')
                    ->where('floor_id', $floorId)
                    ->where('name', $ac->name)
                    ->value('id');

                if (!$roomId) continue;

                DB::table('ac_units')
                    ->where('id', $ac->id)
                    ->update([
                        'room_id' => $roomId,
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('ac_units', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
            $table->dropColumn('room_id');
        });
    }
};