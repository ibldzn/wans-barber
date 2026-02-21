<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeCashAdvance extends Model
{
    protected $fillable = [
        'employee_id',
        'amount',
        'installment_amount',
        'date',
        'description',
        'status',
        'settled_at',
        'payroll_period_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
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

    public function payments(): HasMany
    {
        return $this->hasMany(EmployeeCashAdvancePayment::class);
    }

    public function getTotalPaid(?int $excludePaymentId = null): float
    {
        $query = $this->payments();

        if ($excludePaymentId) {
            $query->where('id', '!=', $excludePaymentId);
        }

        return (float) $query->sum('amount');
    }

    public function getRemainingAmount(?int $excludePaymentId = null): float
    {
        return max(0, (float) $this->amount - $this->getTotalPaid($excludePaymentId));
    }

    public function syncSettlementStatus(): void
    {
        $isSettled = $this->getRemainingAmount() <= 0;

        if ($isSettled) {
            $this->update([
                'status' => 'settled',
                'settled_at' => $this->settled_at ?? now(),
            ]);

            return;
        }

        $this->update([
            'status' => 'open',
            'settled_at' => null,
            'payroll_period_id' => null,
        ]);
    }
}
