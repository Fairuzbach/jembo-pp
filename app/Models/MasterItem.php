<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'ai_tags' => 'array',
        'requires_energy' => 'boolean',
        'is_synced' => 'boolean'
    ];

    // --- Relasi yang Sudah Ada ---
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** 
     * Relasi ke Request Asal 
     */
    public function itemRequest(): BelongsTo
    {
        return $this->belongsTo(ItemRequest::class);
    }

    /** 
     * Relasi Satelit (Data dari 15 Excel)
     */
    public function warehouse(): HasOne
    {
        return $this->hasOne(ItemWarehouse::class);
    }

    public function procurement(): HasOne
    {
        return $this->hasOne(ItemProcurement::class);
    }

    public function orderRule(): HasOne
    {
        return $this->hasOne(ItemOrderRule::class);
    }

    public function serialization(): HasOne
    {
        return $this->hasOne(ItemSerialization::class);
    }
    public function group()
    {
        return $this->belongsTo(ItemGroup::class, 'item_group_id');
    }
}
