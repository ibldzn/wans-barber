<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductConsumable extends Model
{
    protected $fillable = [
        'service_product_id',
        'consumable_product_id',
        'qty_per_unit',
    ];

    protected $casts = [
        'qty_per_unit' => 'integer',
    ];

    public function serviceProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'service_product_id');
    }

    public function consumableProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'consumable_product_id');
    }
}
