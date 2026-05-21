<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemRequest extends Model
{
    protected $guarded = [];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    // Tambahkan relasi ke Item Group agar bisa dipanggil di Dashboard
    public function itemGroup()
    {
        return $this->belongsTo(ItemGroup::class, 'item_group_id');
    }
}
