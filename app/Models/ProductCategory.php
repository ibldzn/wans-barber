<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $fillable = [
        'category_name',
        'category_description',
        'category_type',
        'commission_rate_regular',
        'commission_rate_callout',
    ];

    protected $casts = [
        'commission_rate_regular' => 'decimal:4',
        'commission_rate_callout' => 'decimal:4',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
