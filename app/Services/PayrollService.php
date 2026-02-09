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
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function generatePayslips(PayrollPeriod $period): int
    {
        $employees = Employee::where('is_active', true)->get();

        return DB::transaction(function () use ($period, $employees): int {
            $count = 0;

            foreach ($employees as $employee) {
                $attendanceCount = $employee->attendances()
                    ->whereBetween('date', [$period->start_date, $period->end_date])
                    ->where('status', 'present')
                    ->count();

                $commissionTotal = SaleItem::where('employee_id', $employee->id)
                    ->whereHas('sale', function ($query) use ($period): void {
                        $query->whereBetween('paid_at', [
                            $period->start_date->startOfDay(),
                            $period->end_date->endOfDay(),
                        ]);
                    })
                    ->sum('commission_amount');

                $baseSalary = $attendanceCount * (float) $employee->daily_wage;
                $mealAllowance = $attendanceCount * (float) $employee->meal_allowance_per_day;

                $advances = EmployeeCashAdvance::where('employee_id', $employee->id)
                    ->whereIn('status', ['open'])
                    ->whereDate('date', '<=', $period->end_date)
                    ->get();

                $advanceDeductions = [];
                $deductionTotal = 0.0;

                $advanceIds = $advances->pluck('id')->all();

                $allPayments = EmployeeCashAdvancePayment::query()
                    ->whereIn('employee_cash_advance_id', $advanceIds)
                    ->get()
                    ->groupBy('employee_cash_advance_id');

                $periodPayments = EmployeeCashAdvancePayment::query()
                    ->whereIn('employee_cash_advance_id', $advanceIds)
                    ->where(function ($query) use ($period): void {
                        $query->where('payroll_period_id', $period->id)
                            ->orWhereBetween('paid_at', [$period->start_date, $period->end_date]);
                    })
                    ->get()
                    ->groupBy('employee_cash_advance_id');

                foreach ($advances as $advance) {
                    $paymentsForAdvance = $allPayments->get($advance->id, collect());
                    $totalPaid = (float) $paymentsForAdvance->sum('amount');
                    $remaining = (float) $advance->amount - $totalPaid;

                    if ($remaining <= 0) {
                        if ($advance->status !== 'settled') {
                            $advance->update([
                                'status' => 'settled',
                                'settled_at' => now(),
                                'payroll_period_id' => $period->id,
                            ]);
                        }

                        continue;
                    }

                    $paymentsThisPeriod = $periodPayments->get($advance->id, collect());

                    if ($paymentsThisPeriod->isEmpty() && (float) ($advance->installment_amount ?? 0) > 0) {
                        $autoAmount = min($remaining, (float) $advance->installment_amount);

                        $autoPayment = EmployeeCashAdvancePayment::create([
                            'employee_cash_advance_id' => $advance->id,
                            'payroll_period_id' => $period->id,
                            'amount' => $autoAmount,
                            'paid_at' => $period->end_date,
                            'description' => 'Auto payroll deduction',
                        ]);

                        $paymentsThisPeriod = collect([$autoPayment]);
                        $totalPaid += $autoAmount;
                        $remaining -= $autoAmount;
                    }

                    foreach ($paymentsThisPeriod as $payment) {
                        $advanceDeductions[] = [
                            'advance' => $advance,
                            'payment' => $payment,
                        ];

                        $deductionTotal += (float) $payment->amount;
                    }

                    if ($remaining <= 0) {
                        $advance->update([
                            'status' => 'settled',
                            'settled_at' => now(),
                            'payroll_period_id' => $period->id,
                        ]);
                    }
                }

                $netPay = $baseSalary + $mealAllowance + (float) $commissionTotal - $deductionTotal;

                $payslip = Payslip::updateOrCreate(
                    [
                        'payroll_period_id' => $period->id,
                        'employee_id' => $employee->id,
                    ],
                    [
                        'attendance_count' => $attendanceCount,
                        'base_salary' => $baseSalary,
                        'meal_allowance' => $mealAllowance,
                        'commission_total' => $commissionTotal,
                        'deduction_total' => $deductionTotal,
                        'net_pay' => $netPay,
                        'bank_account_snapshot' => $employee->bank_account,
                    ]
                );

                $payslip->deductions()->where('source_type', 'cash_advance')->delete();

                foreach ($advanceDeductions as $deduction) {
                    $advance = $deduction['advance'];
                    $payment = $deduction['payment'];

                    PayslipDeduction::create([
                        'payslip_id' => $payslip->id,
                        'source_type' => 'cash_advance',
                        'source_id' => $advance->id,
                        'amount' => $payment->amount,
                        'description' => $payment->description ?? $advance->description,
                    ]);
                }

                $count++;
            }

            return $count;
        });
    }

    public function markPayslipPaid(Payslip $payslip, User $user): void
    {
        if ($payslip->paid_at) {
            return;
        }

        DB::transaction(function () use ($payslip, $user): void {
            $payslip->update(['paid_at' => now()]);

            $salaryCategory = FinanceCategory::where('name', 'Gaji & Makan')
                ->where('type', 'expense')
                ->first();

            if (! $salaryCategory) {
                return;
            }

            FinancialTransaction::create([
                'type' => 'expense',
                'category_id' => $salaryCategory->id,
                'amount' => $payslip->net_pay,
                'payment_method_id' => null,
                'occurred_at' => now(),
                'description' => 'Pembayaran gaji ' . $payslip->employee?->emp_name,
                'reference_type' => Payslip::class,
                'reference_id' => $payslip->id,
                'created_by' => $user->id,
            ]);
        });
    }
}
