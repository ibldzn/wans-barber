<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Support\ThermalReceiptFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class SaleThermalPrintController extends Controller
{
    public function __invoke(Sale $sale, Request $request): View|Response
    {
        Gate::authorize('view', $sale);

        $sale->load([
            'cashier:id,emp_name',
            'paymentMethod:id,method_name',
            'items.product:id,product_name',
            'items.employee:id,emp_name',
        ]);

        $isRaw = $request->boolean('raw');
        $lineWidth = $isRaw ? 32 : 38;
        $receiptLines = $this->buildReceiptLines($sale, includeBrandHeader: $isRaw, lineWidth: $lineWidth);
        $brandName = $this->brandName();
        $brandAddressLines = $this->brandAddressLines();

        if ($isRaw) {
            $filename = 'invoice-' . $sale->invoice_no . '-escpos.bin';

            return response($this->buildEscPosPayload($receiptLines), 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        return view('sales.thermal-print', compact('sale', 'receiptLines', 'brandName', 'brandAddressLines'));
    }

    /**
     * @return array<int, string>
     */
    private function buildReceiptLines(Sale $sale, bool $includeBrandHeader = true, int $lineWidth = 32): array
    {
        $formatter = new ThermalReceiptFormatter($lineWidth);
        $lines = [];

        if ($includeBrandHeader) {
            $lines[] = $formatter->center($this->brandName());

            foreach ($this->brandAddressLines() as $addressLine) {
                $lines[] = $formatter->center($addressLine);
            }
        }

        $lines[] = $formatter->hr('=');

        $labelWidth = 10;

        $lines = array_merge($lines, $formatter->labelValueRight('Invoice', $sale->invoice_no, $labelWidth));
        $lines = array_merge($lines, $formatter->labelValueRight(
            'Tanggal',
            $sale->paid_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '-',
            $labelWidth,
        ));
        $lines = array_merge($lines, $formatter->labelValueRight('Kasir', $sale->cashier?->emp_name ?? '-', $labelWidth));
        $lines = array_merge($lines, $formatter->labelValueRight(
            'Pembayaran',
            $sale->paymentMethod?->method_name ?? '-',
            $labelWidth,
        ));

        if ($sale->customer_name) {
            $lines = array_merge($lines, $formatter->labelValueRight('Customer', $sale->customer_name, $labelWidth));
        }

        $lines[] = $formatter->hr('=');

        foreach ($sale->items as $item) {
            $lines = array_merge($lines, $formatter->wrap((string) ($item->product?->product_name ?? '-')));

            $left = sprintf(
                '%d x %s%s',
                (int) $item->qty,
                $this->money((float) $item->unit_price),
                $item->price_tier === 'callout' ? ' (P)' : '',
            );

            $lines = array_merge(
                $lines,
                $formatter->row($left, $this->money((float) $item->line_total)),
            );

            if ($item->employee) {
                $lines = array_merge($lines, $formatter->wrap('Staff: ' . $item->employee->emp_name));
            }

            if ($item->notes) {
                $lines = array_merge($lines, $formatter->wrap('Catatan: ' . $item->notes));
            }

            $lines[] = '';
        }

        while (end($lines) === '') {
            array_pop($lines);
        }

        $lines[] = $formatter->hr('=');
        $lines = array_merge($lines, $formatter->row('Subtotal', $this->money((float) $sale->subtotal)));
        $lines = array_merge($lines, $formatter->row('Diskon', $this->money((float) $sale->discount)));
        $lines = array_merge($lines, $formatter->row('Total', $this->money((float) $sale->total)));

        if ($sale->notes) {
            $lines[] = $formatter->hr('-');
            $lines = array_merge($lines, $formatter->wrap('Catatan: ' . $sale->notes));
        }

        $lines[] = $formatter->hr('=');
        $lines[] = $formatter->center('Terima kasih');

        foreach ($formatter->wrap('Simpan struk ini sebagai bukti pembayaran.') as $footerLine) {
            $lines[] = $formatter->center($footerLine);
        }

        return $lines;
    }

    private function money(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    private function brandName(): string
    {
        return "Wan's Barber & Reflexology";
    }

    /**
     * @return array<int, string>
     */
    private function brandAddressLines(): array
    {
        return [
            'Jl. Boulevard, Grand Wisata',
            'Blok AA 11 No 26',
            'Lambang Jaya, Tambun Selatan',
        ];
    }

    /**
     * @param array<int, string> $lines
     */
    private function buildEscPosPayload(array $lines): string
    {
        $payload = '';
        $payload .= "\x1B\x40"; // Initialize printer
        $payload .= "\x1B\x74\x00"; // Code table CP437 (safe ASCII fallback)
        $payload .= "\x1B\x61\x00"; // Left align (centered lines are space-padded already)
        $payload .= "\x1B\x45\x01"; // Bold on

        foreach ($lines as $line) {
            $payload .= $line . "\n";
        }

        $payload .= "\x1B\x45\x00"; // Bold off
        $payload .= "\n\n\n";
        $payload .= "\x1D\x56\x41\x10"; // Partial cut

        return $payload;
    }
}
