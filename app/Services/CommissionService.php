<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Product;

class CommissionService
{
    public function resolveRate(Product $product, ?Employee $employee, string $priceTier): float
    {
        unset($employee);

        $tier = $priceTier === 'callout' ? 'callout' : 'regular';

        $category = $product->category;

        if (! $category) {
            return 0.0;
        }

        return (float) ($tier === 'callout'
            ? $category->commission_rate_callout
            : $category->commission_rate_regular);
    }
}
