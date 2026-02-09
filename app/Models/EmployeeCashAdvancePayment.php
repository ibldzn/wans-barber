<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCashAdvancePayment extends Model
{
    protected $fillable = [
        'employee_cash_advance_id',
        'payroll_period_id',
        'amount',
        'paid_at',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function cashAdvance(): BelongsTo
    {
        return $this->belongsTo(EmployeeCashAdvance::class, 'employee_cash_advance_id');
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }
}
