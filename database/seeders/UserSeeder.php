<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Imports\UsersImport;
use Maatwebsite\Excel\Facades\Excel;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Lokasi file Excel yang akan di-import
        $file = database_path('seeders/userData.xlsx');

        Excel::import(new UsersImport, $file);
    }
}
