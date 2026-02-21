<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Payslip</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .header { text-align: center; margin-bottom: 16px; }
        .title { font-size: 18px; font-weight: bold; }
        .subtitle { font-size: 12px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f3f3f3; text-align: left; }
        .summary td { border: none; padding: 4px 0; }
        .right { text-align: right; }
        .footer { margin-top: 24px; width: 100%; }
        .footer-row { width: 100%; font-size: 12px; }
        .footer-col { width: 49%; display: inline-block; vertical-align: top; }
        .footer-col.right { text-align: right; }
        .signature-space { height: 80px; }
        .signature-line { padding-top: 8px; }
        .signature-name { padding-top: 6px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">WAN'S BARBERSHOP & REFLEXOLOGY</div>
        <div class="subtitle">Slip Gaji</div>
    </div>

    <table class="summary">
        <tr>
            <td>Nama</td>
            <td>: {{ $payslip->employee?->emp_name }}</td>
            <td>Periode</td>
            <td>: {{ $payslip->payrollPeriod?->name }}</td>
        </tr>
        <tr>
            <td>Rekening</td>
            <td>: {{ $payslip->bank_account_snapshot ?? '-' }}</td>
            <td>Paid At</td>
            <td>: {{ $payslip->paid_at?->format('d M Y H:i') ?? '-' }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Komponen</th>
                <th class="right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Gaji ({{ $payslip->attendance_count }} hari)</td>
                <td class="right">Rp {{ number_format($payslip->base_salary, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Uang Makan</td>
                <td class="right">Rp {{ number_format($payslip->meal_allowance, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Komisi</td>
                <td class="right">Rp {{ number_format($payslip->commission_total, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total Pinjaman</td>
                <td class="right">Rp {{ number_format($payslip->loan_total_before, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Nominal Cicilan</td>
                <td class="right">Rp {{ number_format($payslip->loan_installment_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Sisa Pinjaman</td>
                <td class="right">Rp {{ number_format($payslip->loan_remaining_after, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Potongan</td>
                <td class="right">Rp {{ number_format($payslip->deduction_total, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Total Dibayar</th>
                <th class="right">Rp {{ number_format($payslip->net_pay, 0, ',', '.') }}</th>
            </tr>
        </tbody>
    </table>

    @if ($payslip->deductions->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Detail Potongan</th>
                    <th class="right">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payslip->deductions as $deduction)
                    <tr>
                        <td>{{ $deduction->description ?? $deduction->source_type }}</td>
                        <td class="right">Rp {{ number_format($deduction->amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        <div class="footer-row">
            <div class="footer-col">Dibuat oleh</div>
            <div class="footer-col right">Diterima oleh</div>
        </div>
        <div class="footer-row signature-space"></div>
        <div class="footer-row">
            <div class="footer-col signature-line">_____________________</div>
            <div class="footer-col right signature-line">_____________________</div>
        </div>
        <div class="footer-row">
            <div class="footer-col signature-name">Sistem</div>
            <div class="footer-col right signature-name"></div>
        </div>
    </div>
</body>
</html>
