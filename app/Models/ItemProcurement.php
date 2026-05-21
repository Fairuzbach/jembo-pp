<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemProcurement extends Model
{
    protected $fillable = [
        'master_item_id',
        'buy_from_bp',
        'standard_cost',
        'currency',
        'tax_code',
    ];
}
