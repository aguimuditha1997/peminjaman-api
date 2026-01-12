<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'name'     => 'Admin SDM',
            'email'    => 'sdm@peminjaman.com',
            'password' => bcrypt('password123'),
            'role'     => 'sdm',
        ]);

        // User Role DPT
        User::create([
            'name'     => 'Admin DPT',
            'email'    => 'dpt@peminjaman.com',
            'password' => bcrypt('password123'),
            'role'     => 'dpt',
        ]);
    }
}
