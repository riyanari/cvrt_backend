<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'owner@ridho.test'],
            ['name' => 'Owner Ridho', 'password' => Hash::make('password'), 'role' => 'owner']
        );

        User::updateOrCreate(
            ['email' => 'client@ridho.test'],
            ['name' => 'Client A', 'password' => Hash::make('password'), 'role' => 'client', 'phone' => '0812xxxx']
        );

        User::updateOrCreate(
            ['email' => 'teknisi@ridho.test'],
            ['name' => 'Teknisi A', 'password' => Hash::make('password'), 'role' => 'teknisi', 'spesialisasi' => 'AC Split', 'rating' => 4.7]
        );
    }
}
