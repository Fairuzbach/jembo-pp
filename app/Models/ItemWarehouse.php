<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemWarehouse extends Model
{
    protected $fillable = [
        'master_item_id', // (Opsional, tapi baik untuk ada)
        'warehouse_code',
        'weight',
        'length',
        'width',
        'height',
        'hazardous_material',
        'class_of_risk',
    ];
}
