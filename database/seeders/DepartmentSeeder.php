<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Daftar data yang Anda berikan
        $departments = [
            ['code' => 'PP', 'name' => 'PRODUCTION PLANNING'],
            ['code' => 'LV', 'name' => 'LOW VOLTAGE'],
            ['code' => 'MV', 'name' => 'MEDIUM VOLTAGE'],
            ['code' => 'FO', 'name' => 'FIBER OPTIC'],
            ['code' => 'MT', 'name' => 'MAINTENANCE'],
            ['code' => 'PE', 'name' => 'PROCESS ENGINEERING'],
            ['code' => 'FM', 'name' => 'FINANCE'],
            ['code' => 'ACC', 'name' => 'ACCOUNTING'],
            ['code' => 'ITM', 'name' => 'INFORMATION TECHNOLOGY'],
            ['code' => 'SC', 'name' => 'PROCUREMENT'],
            ['code' => 'SS', 'name' => 'SALES SUPPORT'],
            ['code' => 'S1', 'name' => 'SALES 1'],
            ['code' => 'S2', 'name' => 'SALES 2'],
            ['code' => 'MKT', 'name' => 'MARKETING'],
            ['code' => 'QA', 'name' => 'QUALITY ASSURANCE'],
            ['code' => 'RND', 'name' => 'RESEARCH AND DEVELOPMENT'],
            ['code' => 'FH', 'name' => 'FACILITY'],
            ['code' => 'HC', 'name' => 'HUMAN CAPITAL'],
            ['code' => 'GA', 'name' => 'GENERAL AFFAIR'],
        ];

        DB::transaction(function () use ($departments) {
            foreach ($departments as $data) {
                // 2. Masukkan ke tabel departments
                $dept = Department::updateOrCreate(
                    ['code' => $data['code']],
                    ['name' => $data['name']]
                );
            }
        });

        $this->command->info('Master Department berhasil diisi dan User berhasil dihubungkan!');
    }
}
