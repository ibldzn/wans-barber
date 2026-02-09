<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'emp_name',
        'emp_phone',
        'role',
        'bank_account',
        'daily_wage',
        'meal_allowance_per_day',
        'commission_rate_override_regular',
        'commission_rate_override_callout',
        'is_active',
        'user_id',
    ];

    protected $casts = [
        'daily_wage' => 'decimal:2',
        'meal_allowance_per_day' => 'decimal:2',
        'commission_rate_override_regular' => 'decimal:4',
        'commission_rate_override_callout' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salesAsCashier(): HasMany
    {
        return $this->hasMany(Sale::class, 'cashier_id');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class);
    }

    public function cashAdvances(): HasMany
    {
        return $this->hasMany(EmployeeCashAdvance::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }
}
