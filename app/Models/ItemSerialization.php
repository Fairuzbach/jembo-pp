<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemSerialization extends Model
{
    protected $fillable = [
        'master_item_id',
        'is_serialized',
        'warranty_period_months',
    ];
}
