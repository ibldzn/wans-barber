<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Product;

class CommissionService
{
    public function resolveRate(Product $product, ?Employee $employee, string $priceTier): float
    {
        $tier = $priceTier === 'callout' ? 'callout' : 'regular';

        $productOverride = $tier === 'callout'
            ? $product->commission_rate_override_callout
            : $product->commission_rate_override_regular;

        if ($productOverride !== null) {
            return (float) $productOverride;
        }

        if ($employee) {
            $employeeOverride = $tier === 'callout'
                ? $employee->commission_rate_override_callout
                : $employee->commission_rate_override_regular;

            if ($employeeOverride !== null) {
                return (float) $employeeOverride;
            }
        }

        $category = $product->category;

        if (! $category) {
            return 0.0;
        }

        return (float) ($tier === 'callout'
            ? $category->commission_rate_callout
            : $category->commission_rate_regular);
    }
}
