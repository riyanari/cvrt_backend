<?php

namespace Database\Seeders;

use App\Models\AcUnit;
use App\Models\Location;
use App\Models\Service;
use App\Models\ServiceItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RidhoTeknikSeeder extends Seeder
{
    public function run(): void
    {
        // =========================
        // HAPUS DATA LAMA (DEV ONLY)
        // =========================
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // urutan aman
        DB::table('service_items')->truncate();
        DB::table('services')->truncate();
        DB::table('ac_units')->truncate();
        DB::table('location_user')->truncate();
        DB::table('locations')->truncate();
        DB::table('users')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // =========================
        // USERS (DATA ASLI)
        // =========================
        $owner = User::create([
            'name' => 'Ridho Teknik',
            'email' => 'ridhoac',
            'password' => Hash::make('#Ridho123'),
            'role' => 'owner',
            'phone' => '08112761889',
            'spesialisasi' => null,
        ]);

        $teknisi = User::create([
            'name' => 'Wakhid',
            'email' => 'wakhid',
            'password' => Hash::make('#cvrt123'),
            'role' => 'teknisi',
            'phone' => '081329182006',
            'spesialisasi' => 'service',
        ]);

        $clientPic1 = User::create([
            'name' => 'Mandiri',
            'email' => 'keppic',
            'password' => Hash::make('#Acmandiri'),
            'role' => 'client',
            'phone' => '0888888888',
            'spesialisasi' => null,
        ]);

        $clientPic2 = User::create([
            'name' => 'Mandiri',
            'email' => 'picmess',
            'password' => Hash::make('#Acmandiri'),
            'role' => 'client',
            'phone' => '0888888888',
            'spesialisasi' => null,
        ]);

        $clientPic3 = User::create([
            'name' => 'Mandiri',
            'email' => 'picmain',
            'password' => Hash::make('#Acmandiri'),
            'role' => 'client',
            'phone' => '0888888888',
            'spesialisasi' => null,
        ]);

        // =========================
        // LOCATION (DATA ASLI)
        // =========================
        $lokasi = Location::create([
            'name' => 'Wisma Mandiri Singosari',
            'address' => 'Jl. Singosari Raya No.48, Pleburan, Kec. Semarang Sel., Kota Semarang, Jawa Tengah 50241',
            'latitude' => -7.000064642347682,
            'longitude' => 110.42648568988233,
            'place_id' => null,
            'gmaps_url' => 'https://maps.app.goo.gl/Di8j6bCbhRS9QsbL6',
            'jumlah_ac' => 0,
            'last_service' => '2026-02-02 19:12:24',
        ]);

        // =========================
        // PIVOT: LOCATION_USER
        // (3 akun client punya akses ke 1 lokasi)
        // =========================
        $lokasi->users()->syncWithoutDetaching([
            $clientPic1->id,
            $clientPic2->id,
            $clientPic3->id,
        ]);

        // =========================
        // AC UNITS (DATA ASLI)
        // =========================
        $lastService = '2026-02-02 19:12:24';

        $units = [
            ['Kamar 1', 'Daikin', 'Split/FTV15AXV14', '0,5 PK'],
            ['Kamar 2', 'Daikin', 'Split/FTV15AXV14', '0,5 PK'],
            ['Kamar 3', 'Daikin', 'Split/FTV15AXV14', '0,5 PK'],
            ['Kamar 4', 'Daikin', 'Split/FTV15AXV14', '0,5 PK'],
            ['Kamar 5', 'Daikin', 'Split/FTV15AXV14', '0,5 PK'],
            ['Kamar 6', 'Daikin', 'Split/FTV15AXV14', '0,5 PK'],
            ['Kamar 7', 'Daikin', 'Split/FTNE25MV14', '1 PK'],
            ['Kamar 8', 'Daikin', 'Split/FTV15AXV14', '0,5 PK'],
            ['Ruang Tamu', 'Daikin', 'Split/FTV15AXV14', '0,5 PK'],
            ['Kamar No Name', 'Daikin', 'Split/CS-YN7SKJ', '3/4 PK'],
        ];

        foreach ($units as [$name, $brand, $type, $capacity]) {
            AcUnit::create([
                'location_id' => $lokasi->id,
                'name' => $name,
                'brand' => $brand,
                'type' => $type,
                'capacity' => $capacity,
                'last_service' => $lastService,
            ]);
        }

        // sync jumlah_ac lokasi
        $lokasi->update([
            'jumlah_ac' => $lokasi->acUnits()->count(),
        ]);

        // =========================
        // INFO
        // =========================
        $this->command->info('Seeding data asli (pivot) selesai!');
        $this->command->info('==============================');
        $this->command->info('Total Users: ' . User::count());
        $this->command->info('Total Locations: ' . Location::count());
        $this->command->info('Total Location-User: ' . DB::table('location_user')->count());
        $this->command->info('Total AC Units: ' . AcUnit::count());
        $this->command->info('Total Services: ' . Service::count());
        $this->command->info('Total Service Items: ' . ServiceItem::count());
        $this->command->info('==============================');
    }
}
