<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PayslipPdfController extends Controller
{
    public function __invoke(Payslip $payslip): Response
    {
        $user = auth()->user();

        if (! $user && (! $user->hasRole('admin') && ! $user->can('view_payslip'))) {
            abort(403);
        }

        $payslip->load(['employee', 'payrollPeriod', 'deductions']);

        $pdf = Pdf::loadView('payslips.pdf', [
            'payslip' => $payslip,
        ])->setPaper('a4');

        $filename = 'payslip-' . $payslip->employee?->emp_name . '-' . $payslip->payrollPeriod?->name . '.pdf';

        return $pdf->download($filename);
    }
}
