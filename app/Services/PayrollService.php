<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeCashAdvance;
use App\Models\EmployeeCashAdvancePayment;
use App\Models\FinanceCategory;
use App\Models\FinancialTransaction;
use App\Models\Payslip;
use App\Models\PayslipDeduction;
use App\Models\PayrollPeriod;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function generatePayslips(PayrollPeriod $period): int
    {
        $period->refresh();

        if ($period->period_type === 'daily') {
            return $this->generateDailyPayslips($period);
        }

        return $this->generateMonthlyPayslips($period);
    }

    protected function generateDailyPayslips(PayrollPeriod $period): int
    {
        $employees = Employee::where('is_active', true)
            ->whereIn('role', ['barber', 'reflexology'])
            ->get();

        return DB::transaction(function () use ($period, $employees): int {
            $count = 0;

            foreach ($employees as $employee) {
                $attendanceCount = $this->getAttendanceCount($employee, $period);
                $commissionTotal = $this->getCommissionTotal($employee, $period);
                $netPay = $commissionTotal;

                $payslip = Payslip::updateOrCreate(
                    [
                        'payroll_period_id' => $period->id,
                        'employee_id' => $employee->id,
                    ],
                    [
                        'attendance_count' => $attendanceCount,
                        'base_salary' => 0,
                        'meal_allowance' => 0,
                        'commission_total' => $commissionTotal,
                        'loan_total_before' => 0,
                        'loan_installment_amount' => 0,
                        'loan_remaining_after' => 0,
                        'deduction_total' => 0,
                        'net_pay' => $netPay,
                        'bank_account_snapshot' => $employee->bank_account,
                    ]
                );

                $payslip->deductions()->where('source_type', 'cash_advance')->delete();
                $count++;
            }

            return $count;
        });
    }

    protected function generateMonthlyPayslips(PayrollPeriod $period): int
    {
        $employees = Employee::where('is_active', true)
            ->whereIn('role', ['barber', 'reflexology', 'kasir', 'ob'])
            ->get();

        return DB::transaction(function () use ($period, $employees): int {
            $count = 0;

            foreach ($employees as $employee) {
                $attendanceCount = $this->getAttendanceCount($employee, $period);

                $baseSalary = in_array($employee->role, ['kasir', 'ob'], true)
                    ? (float) $employee->monthly_salary
                    : 0.0;

                $mealRate = (float) $employee->meal_allowance_per_day > 0
                    ? (float) $employee->meal_allowance_per_day
                    : 15000.0;

                $mealAllowance = $attendanceCount * $mealRate;

                [$loanTotalBefore, $installmentAmount, $loanRemainingAfter, $deductionRows] =
                    $this->buildLoanSnapshotAndDeductions($employee, $period);

                $deductionTotal = $installmentAmount;
                $netPay = $baseSalary + $mealAllowance - $deductionTotal;

                $payslip = Payslip::updateOrCreate(
                    [
                        'payroll_period_id' => $period->id,
                        'employee_id' => $employee->id,
                    ],
                    [
                        'attendance_count' => $attendanceCount,
                        'base_salary' => $baseSalary,
                        'meal_allowance' => $mealAllowance,
                        'commission_total' => 0,
                        'loan_total_before' => $loanTotalBefore,
                        'loan_installment_amount' => $installmentAmount,
                        'loan_remaining_after' => $loanRemainingAfter,
                        'deduction_total' => $deductionTotal,
                        'net_pay' => $netPay,
                        'bank_account_snapshot' => $employee->bank_account,
                    ]
                );

                $payslip->deductions()->where('source_type', 'cash_advance')->delete();

                foreach ($deductionRows as $row) {
                    PayslipDeduction::create([
                        'payslip_id' => $payslip->id,
                        'source_type' => 'cash_advance',
                        'source_id' => $row['advance_id'],
                        'amount' => $row['amount'],
                        'description' => $row['description'],
                    ]);
                }

                $count++;
            }

            return $count;
        });
    }

    protected function getAttendanceCount(Employee $employee, PayrollPeriod $period): int
    {
        return $employee->attendances()
            ->whereBetween('date', [$period->start_date, $period->end_date])
            ->where('status', 'present')
            ->count();
    }

    protected function getCommissionTotal(Employee $employee, PayrollPeriod $period): float
    {
        return (float) SaleItem::where('employee_id', $employee->id)
            ->whereHas('sale', function ($query) use ($period): void {
                $query->whereBetween('paid_at', [
                    $period->start_date->copy()->startOfDay(),
                    $period->end_date->copy()->endOfDay(),
                ]);
            })
            ->sum('commission_amount');
    }

    /**
     * @return array{0: float, 1: float, 2: float, 3: array<int, array{advance_id: int, amount: float, description: string}>}
     */
    protected function buildLoanSnapshotAndDeductions(Employee $employee, PayrollPeriod $period): array
    {
        $start = $period->start_date->copy()->startOfDay();
        $end = $period->end_date->copy()->endOfDay();

        $advances = EmployeeCashAdvance::where('employee_id', $employee->id)
            ->whereDate('date', '<=', $period->end_date)
            ->get();

        if ($advances->isEmpty()) {
            return [0.0, 0.0, 0.0, []];
        }

        $paymentsByAdvance = EmployeeCashAdvancePayment::query()
            ->whereIn('employee_cash_advance_id', $advances->pluck('id')->all())
            ->orderBy('paid_at')
            ->get()
            ->groupBy('employee_cash_advance_id');

        $loanTotalBefore = 0.0;
        $installmentAmount = 0.0;
        $loanRemainingAfter = 0.0;
        $deductionRows = [];

        foreach ($advances as $advance) {
            /** @var Collection<int, EmployeeCashAdvancePayment> $payments */
            $payments = $paymentsByAdvance->get($advance->id, collect());

            $paidBefore = (float) $payments
                ->filter(fn (EmployeeCashAdvancePayment $payment): bool => $payment->paid_at->lt($start))
                ->sum('amount');

            $remainingBefore = max(0, (float) $advance->amount - $paidBefore);
            $loanTotalBefore += $remainingBefore;

            $periodPayments = $payments
                ->filter(fn (EmployeeCashAdvancePayment $payment): bool => $this->isPaymentIncludedInPeriod($payment, $period, $start, $end))
                ->values();

            $remainingCap = $remainingBefore;

            foreach ($periodPayments as $payment) {
                if ($remainingCap <= 0) {
                    break;
                }

                $appliedAmount = min($remainingCap, (float) $payment->amount);

                if ($appliedAmount <= 0) {
                    continue;
                }

                $deductionRows[] = [
                    'advance_id' => $advance->id,
                    'amount' => $appliedAmount,
                    'description' => $payment->description ?: ($advance->description ?: 'Cicilan kasbon'),
                ];

                $installmentAmount += $appliedAmount;
                $remainingCap -= $appliedAmount;
            }

            $remainingAfter = $remainingCap;
            $loanRemainingAfter += $remainingAfter;

            $this->syncAdvanceStatus($advance, (float) $payments->sum('amount'), $period);
        }

        return [
            $loanTotalBefore,
            $installmentAmount,
            $loanRemainingAfter,
            $deductionRows,
        ];
    }

    protected function isPaymentIncludedInPeriod(
        EmployeeCashAdvancePayment $payment,
        PayrollPeriod $period,
        Carbon $start,
        Carbon $end,
    ): bool {
        if ((int) ($payment->payroll_period_id ?? 0) === (int) $period->id) {
            return true;
        }

        if ($payment->payroll_period_id !== null) {
            return false;
        }

        return $payment->paid_at->between($start, $end);
    }

    protected function syncAdvanceStatus(EmployeeCashAdvance $advance, float $totalPaid, PayrollPeriod $period): void
    {
        $isSettled = $totalPaid >= (float) $advance->amount;

        if ($isSettled && $advance->status !== 'settled') {
            $advance->update([
                'status' => 'settled',
                'settled_at' => now(),
                'payroll_period_id' => $period->id,
            ]);

            return;
        }

        if (! $isSettled && $advance->status !== 'open') {
            $advance->update([
                'status' => 'open',
                'settled_at' => null,
                'payroll_period_id' => null,
            ]);
        }
    }

    public function markPayslipPaid(Payslip $payslip, User $user): void
    {
        if ($payslip->paid_at) {
            return;
        }

        $periodType = $payslip->payrollPeriod?->period_type ?? 'monthly';
        $categoryName = $periodType === 'daily' ? 'Komisi Pegawai' : 'Gaji & Makan';

        DB::transaction(function () use ($payslip, $user, $categoryName, $periodType): void {
            $payslip->update(['paid_at' => now()]);

            $category = FinanceCategory::where('name', $categoryName)
                ->where('type', 'expense')
                ->first();

            if (! $category) {
                return;
            }

            FinancialTransaction::create([
                'type' => 'expense',
                'category_id' => $category->id,
                'amount' => $payslip->net_pay,
                'payment_method_id' => null,
                'occurred_at' => now(),
                'description' => $periodType === 'daily'
                    ? 'Pembayaran komisi harian ' . $payslip->employee?->emp_name
                    : 'Pembayaran gaji bulanan ' . $payslip->employee?->emp_name,
                'reference_type' => Payslip::class,
                'reference_id' => $payslip->id,
                'created_by' => $user->id,
            ]);
        });
    }
}
