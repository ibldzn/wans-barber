<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'attendance_count',
        'base_salary',
        'meal_allowance',
        'commission_total',
        'loan_total_before',
        'loan_installment_amount',
        'loan_remaining_after',
        'deduction_total',
        'net_pay',
        'bank_account_snapshot',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'meal_allowance' => 'decimal:2',
        'commission_total' => 'decimal:2',
        'loan_total_before' => 'decimal:2',
        'loan_installment_amount' => 'decimal:2',
        'loan_remaining_after' => 'decimal:2',
        'deduction_total' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(PayslipDeduction::class);
    }
}
