<?php

namespace App\Filament\Pages\Reports;

use App\Models\FinancialTransaction;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\CarbonPeriod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class ProfitLossPage extends Page
{
    use InteractsWithForms;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $title = 'Rekap Pendapatan & Pengeluaran';

    protected static ?string $navigationLabel = 'Pendapatan vs Pengeluaran';

    protected string $view = 'filament.pages.reports.profit-loss';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Filter')->schema([
                    DatePicker::make('start_date')
                        ->label('Mulai')
                        ->live()
                        ->beforeOrEqual('end_date')
                        ->required(),
                    DatePicker::make('end_date')
                        ->label('Selesai')
                        ->live()
                        ->afterOrEqual('start_date')
                        ->required(),
                ])
                    ->columnSpanFull()
                    ->inlineLabel()
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function getViewData(): array
    {
        $start = Carbon::parse($this->data['start_date'] ?? now()->startOfMonth())->startOfDay();
        $end = Carbon::parse($this->data['end_date'] ?? now()->endOfMonth())->endOfDay();

        $transactions = FinancialTransaction::query()
            ->whereBetween('occurred_at', [$start, $end]);

        $incomeTotal = (float) (clone $transactions)->where('type', 'income')->sum('amount');
        $expenseTotal = (float) (clone $transactions)->where('type', 'expense')->sum('amount');
        $netTotal = $incomeTotal - $expenseTotal;

        $incomeByCategory = FinancialTransaction::query()
            ->with('category')
            ->where('type', 'income')
            ->whereBetween('occurred_at', [$start, $end])
            ->get()
            ->groupBy(fn ($item) => $item->category?->name ?? 'Uncategorized')
            ->map(fn ($items) => (float) $items->sum('amount'))
            ->sortDesc()
            ->toArray();

        $expenseByCategory = FinancialTransaction::query()
            ->with('category')
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$start, $end])
            ->get()
            ->groupBy(fn ($item) => $item->category?->name ?? 'Uncategorized')
            ->map(fn ($items) => (float) $items->sum('amount'))
            ->sortDesc()
            ->toArray();

        $dailyTotals = FinancialTransaction::query()
            ->selectRaw('DATE(occurred_at) as tx_date, type, SUM(amount) as total_amount')
            ->whereBetween('occurred_at', [$start, $end])
            ->groupBy('tx_date', 'type')
            ->get()
            ->groupBy('tx_date')
            ->map(function ($items): array {
                $income = (float) $items->firstWhere('type', 'income')?->total_amount;
                $expense = (float) $items->firstWhere('type', 'expense')?->total_amount;

                return [
                    'income' => $income,
                    'expense' => $expense,
                ];
            });

        $dailyRows = collect(CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()))
            ->map(function (Carbon $date) use ($dailyTotals): array {
                $key = $date->toDateString();
                $income = (float) ($dailyTotals->get($key)['income'] ?? 0);
                $expense = (float) ($dailyTotals->get($key)['expense'] ?? 0);

                return [
                    'date' => $key,
                    'income' => $income,
                    'expense' => $expense,
                    'net' => $income - $expense,
                ];
            })
            ->values()
            ->all();

        return [
            'grossIncome' => $incomeTotal,
            'totalExpense' => $expenseTotal,
            'netProfit' => $netTotal,
            'dailyRows' => $dailyRows,
            'incomeByCategory' => $incomeByCategory,
            'expenseByCategory' => $expenseByCategory,
            'start' => $start,
            'end' => $end,
        ];
    }
}
