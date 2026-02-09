<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeCashAdvance;
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
                    ->where('status', 'open')
                    ->whereDate('date', '<=', $period->end_date)
                    ->get();

                $deductionTotal = (float) $advances->sum('amount');

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

                $payslip->deductions()->delete();

                foreach ($advances as $advance) {
                    PayslipDeduction::create([
                        'payslip_id' => $payslip->id,
                        'source_type' => 'cash_advance',
                        'amount' => $advance->amount,
                        'description' => $advance->description,
                    ]);

                    $advance->update([
                        'status' => 'settled',
                        'settled_at' => now(),
                        'payroll_period_id' => $period->id,
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
