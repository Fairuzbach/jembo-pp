<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequestItem extends Model
{
    // Membuka proteksi mass assignment
    protected $guarded = [];

    // Relasi balik ke header PP
    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    // Relasi ke data master barang
    public function masterItem()
    {
        return $this->belongsTo(MasterItem::class);
    }
}
