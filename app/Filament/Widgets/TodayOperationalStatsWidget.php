<?php

namespace App\Filament\Widgets;

use App\Models\FinancialTransaction;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodayOperationalStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    protected ?string $heading = 'Kinerja Operasional Hari Ini';

    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->isAdmin() || $user->isKasir();
    }

    protected function getStats(): array
    {
        $start = now()->startOfDay();
        $end = now()->endOfDay();

        $grossIncome = (float) FinancialTransaction::query()
            ->where('type', 'income')
            ->whereBetween('occurred_at', [$start, $end])
            ->sum('amount');

        $totalExpense = (float) FinancialTransaction::query()
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$start, $end])
            ->sum('amount');

        $netProfit = $grossIncome - $totalExpense;

        $salesCount = Sale::query()
            ->whereBetween('paid_at', [$start, $end])
            ->count();

        return [
            Stat::make('Gross Income', $this->formatCurrency($grossIncome))
                ->description('Omzet hari ini')
                ->color('success'),
            Stat::make('Total Expense', $this->formatCurrency($totalExpense))
                ->description('Pengeluaran hari ini')
                ->color('danger'),
            Stat::make('Net Profit', $this->formatCurrency($netProfit))
                ->description($netProfit >= 0 ? 'Positif' : 'Minus')
                ->color($netProfit >= 0 ? 'success' : 'danger'),
            Stat::make('Jumlah Penjualan', number_format($salesCount))
                ->description('Transaksi sales hari ini')
                ->color('info'),
        ];
    }

    protected function formatCurrency(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
