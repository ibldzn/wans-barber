<?php

namespace App\Filament\Pages\Reports;

use App\Models\Sale;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class PaymentRecapPage extends Page
{
    use InteractsWithForms;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $title = 'Rekap Metode Pembayaran';

    protected string $view = 'filament.pages.reports.payment-recap';

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

        $sales = Sale::with('paymentMethod')
            ->whereBetween('paid_at', [$start, $end])
            ->get();

        $byDate = $sales->groupBy(fn($sale) => $sale->paid_at->toDateString())
            ->map(function ($items) {
                return $items->groupBy(fn($sale) => $sale->paymentMethod?->method_name ?? 'Unknown')
                    ->map(fn($group) => $group->sum('total'))
                    ->toArray();
            })
            ->toArray();

        $totalsByMethod = $sales->groupBy(fn($sale) => $sale->paymentMethod?->method_name ?? 'Unknown')
            ->map(fn($group) => $group->sum('total'))
            ->toArray();

        return [
            'byDate' => $byDate,
            'totalsByMethod' => $totalsByMethod,
            'start' => $start,
            'end' => $end,
        ];
    }
}
