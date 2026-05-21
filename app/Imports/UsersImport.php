<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Cari ID departemen berdasarkan nama organisasi di Excel
        $dept = \App\Models\Department::where('name', $row['organization'])->first();

        return new User([
            'nik'           => $row['employee_id'],
            'name'          => $row['full_name'],
            'department_id' => $dept ? $dept->id : null, // Pasang ID-nya langsung
            'job_position'  => $row['job_position'],
            'job_level'     => $row['job_level'],
            'password'      => Hash::make('jembopass'),
        ]);
    }
}
