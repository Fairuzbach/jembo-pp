<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemOrderRule extends Model
{
    protected $fillable = [
        'master_item_id',
        'safety_stock',
        'reorder_point',
        'min_order_qty',
        'max_order_qty',
    ];
}
