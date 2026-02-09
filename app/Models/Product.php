<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'product_name',
        'product_price',
        'product_price_other',
        'product_description',
        'product_type',
        'track_stock',
        'cost_price',
        'reorder_level',
        'is_active',
        'commission_rate_override_regular',
        'commission_rate_override_callout',
    ];

    protected $casts = [
        'product_price' => 'decimal:2',
        'product_price_other' => 'decimal:2',
        'track_stock' => 'boolean',
        'cost_price' => 'decimal:2',
        'is_active' => 'boolean',
        'commission_rate_override_regular' => 'decimal:4',
        'commission_rate_override_callout' => 'decimal:4',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function consumables(): HasMany
    {
        return $this->hasMany(ProductConsumable::class, 'service_product_id');
    }

    public function asConsumableFor(): HasMany
    {
        return $this->hasMany(ProductConsumable::class, 'consumable_product_id');
    }
}
