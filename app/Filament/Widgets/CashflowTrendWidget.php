<?php

namespace App\Filament\Widgets;

use App\Models\FinancialTransaction;
use Filament\Widgets\LineChartWidget;

class CashflowTrendWidget extends LineChartWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Trend Cashflow';

    protected ?string $description = 'Perbandingan pendapatan dan pengeluaran';

    protected ?string $pollingInterval = '60s';

    public ?string $filter = '30';

    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->isAdmin() || $user->isKasir();
    }

    protected function getFilters(): ?array
    {
        return [
            '14' => '14 Hari',
            '30' => '30 Hari',
        ];
    }

    protected function getData(): array
    {
        $days = in_array($this->filter, ['14', '30'], true) ? (int) $this->filter : 30;

        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        $raw = FinancialTransaction::query()
            ->selectRaw('DATE(occurred_at) as tx_date, type, SUM(amount) as total_amount')
            ->whereBetween('occurred_at', [$start, $end])
            ->groupBy('tx_date', 'type')
            ->get();

        $grouped = [];

        foreach ($raw as $row) {
            $date = (string) $row->tx_date;
            $type = (string) $row->type;
            $grouped[$date][$type] = (float) $row->total_amount;
        }

        $labels = [];
        $incomeData = [];
        $expenseData = [];

        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();

            $labels[] = $cursor->format('d M');
            $incomeData[] = (float) ($grouped[$key]['income'] ?? 0);
            $expenseData[] = (float) ($grouped[$key]['expense'] ?? 0);

            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Income',
                    'data' => $incomeData,
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.2)',
                ],
                [
                    'label' => 'Expense',
                    'data' => $expenseData,
                    'borderColor' => '#dc2626',
                    'backgroundColor' => 'rgba(220, 38, 38, 0.2)',
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
