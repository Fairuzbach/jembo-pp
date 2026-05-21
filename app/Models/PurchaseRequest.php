<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    // Membuka proteksi mass assignment
    protected $guarded = [];

    // Relasi ke User (Requester)
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    // Relasi ke item-item barang
    public function items()
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }
}
