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
        'monthly_salary',
        'meal_allowance_per_day',
        'is_active',
        'user_id',
    ];

    protected $casts = [
        'monthly_salary' => 'decimal:2',
        'meal_allowance_per_day' => 'decimal:2',
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
