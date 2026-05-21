<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    protected $fillable = [
        'nik',
        'name',
        'department',
        'job_position',
        'job_level',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function dept()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function getAuthIdentifierName()
    {
        return 'nik';
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
