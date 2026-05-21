<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // 1. Induk utama (harus paling pertama)
            DepartmentSeeder::class,

            // 2. Data User (butuh Department ID)
            UserSeeder::class,
            // 3. Relasi atau cabang dari Department
            // LinkDepartmentSeeder::class,
            // 4. Data Barang (butuh Department ID)
            MasterItemSeeder::class,
        ]);
    }
}
