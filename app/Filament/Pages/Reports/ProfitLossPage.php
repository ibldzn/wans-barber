<?php

namespace App\Filament\Pages\Reports;

use App\Models\FinancialTransaction;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
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

    protected static ?string $title = 'Laporan Laba Rugi';

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
                        ->required(),
                    DatePicker::make('end_date')
                        ->label('Selesai')
                        ->live()
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

        $income = FinancialTransaction::where('type', 'income')
            ->whereBetween('occurred_at', [$start, $end]);

        $expense = FinancialTransaction::where('type', 'expense')
            ->whereBetween('occurred_at', [$start, $end]);

        $incomeTotal = (float) $income->sum('amount');
        $expenseTotal = (float) $expense->sum('amount');
        $net = $incomeTotal - $expenseTotal;

        $incomeByCategory = FinancialTransaction::with('category')
            ->where('type', 'income')
            ->whereBetween('occurred_at', [$start, $end])
            ->get()
            ->groupBy(fn($item) => $item->category?->name ?? 'Uncategorized')
            ->map(fn($items) => $items->sum('amount'))
            ->toArray();

        $expenseByCategory = FinancialTransaction::with('category')
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$start, $end])
            ->get()
            ->groupBy(fn($item) => $item->category?->name ?? 'Uncategorized')
            ->map(fn($items) => $items->sum('amount'))
            ->toArray();

        return [
            'incomeTotal' => $incomeTotal,
            'expenseTotal' => $expenseTotal,
            'netTotal' => $net,
            'incomeByCategory' => $incomeByCategory,
            'expenseByCategory' => $expenseByCategory,
            'start' => $start,
            'end' => $end,
        ];
    }
}
