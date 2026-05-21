<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Seeder;

class LinkDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::all();

        foreach ($departments as $dept) {
            // Cari semua user yang kolom teks 'department'-nya sama dengan nama departemen ini
            // Lalu isi kolom 'department_id' dengan ID departemen tersebut
            User::where('department', $dept->name)->update([
                'department_id' => $dept->id
            ]);
        }
    }
}
