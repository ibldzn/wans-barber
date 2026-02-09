<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCashAdvance extends Model
{
    protected $fillable = [
        'employee_id',
        'amount',
        'date',
        'description',
        'status',
        'settled_at',
        'payroll_period_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'settled_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }
}
