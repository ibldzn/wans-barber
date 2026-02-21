<?php

namespace App\Filament\Pages\Reports;

use App\Models\Employee;
use App\Models\Product;
use App\Models\SaleItem;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class StaffServiceRecapPage extends Page
{
    use InteractsWithForms;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $title = 'Rekap Per Petugas';

    protected string $view = 'filament.pages.reports.staff-service-recap';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->isAdmin() || $user->isKasir();
    }

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'employee_id' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Filter')->schema([
                    Select::make('employee_id')
                        ->label('Petugas')
                        ->options(fn () => Employee::query()
                            ->whereIn('role', ['barber', 'reflexology'])
                            ->where('is_active', true)
                            ->orderBy('emp_name')
                            ->pluck('emp_name', 'id'))
                        ->searchable()
                        ->live(),
                    DatePicker::make('start_date')
                        ->label('Mulai')
                        ->required()
                        ->live(),
                    DatePicker::make('end_date')
                        ->label('Selesai')
                        ->required()
                        ->live(),
                ])
                    ->columnSpanFull()
                    ->inlineLabel()
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function getViewData(): array
    {
        $employeeId = (int) ($this->data['employee_id'] ?? 0);
        $start = Carbon::parse($this->data['start_date'] ?? now()->startOfMonth())->startOfDay();
        $end = Carbon::parse($this->data['end_date'] ?? now()->endOfMonth())->endOfDay();

        $employee = $employeeId > 0
            ? Employee::query()->whereKey($employeeId)->first()
            : null;

        $serviceProductsQuery = Product::query()
            ->with(['category', 'consumables.consumableProduct'])
            ->where('product_type', 'service')
            ->whereHas('category', fn (Builder $query) => $query->where('category_type', 'service'));

        if ($employee) {
            $serviceProductsQuery = $this->applyEmployeeServiceScope($serviceProductsQuery, $employee);
        }

        $serviceProducts = $serviceProductsQuery
            ->orderBy('product_name')
            ->get();

        if (! $employee) {
            return [
                'employee' => null,
                'start' => $start,
                'end' => $end,
                'rows' => $serviceProducts->map(function (Product $product): array {
                    return [
                        'product' => $product,
                        'qty' => 0,
                        'total' => 0.0,
                    ];
                })->all(),
                'summaries' => [],
            ];
        }

        $serviceProductIds = $serviceProducts->pluck('id')->all();

        $grouped = SaleItem::query()
            ->selectRaw('product_id, SUM(qty) as qty_sum, SUM(line_total) as total_sum, SUM(commission_amount) as commission_sum')
            ->where('employee_id', $employee->id)
            ->whereIn('product_id', $serviceProductIds)
            ->whereHas('sale', function ($query) use ($start, $end): void {
                $query->whereBetween('paid_at', [$start, $end]);
            })
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $rows = [];

        foreach ($serviceProducts as $product) {
            $aggregate = $grouped->get($product->id);
            $qty = (int) round((float) ($aggregate->qty_sum ?? 0));
            $total = (float) ($aggregate->total_sum ?? 0);
            $commission = (float) ($aggregate->commission_sum ?? 0);

            $rows[] = [
                'product' => $product,
                'qty' => $qty,
                'total' => $total,
                'commission' => $commission,
                'barber_share' => max(0, $total - $commission),
                'consumable_cost' => $this->calculateConsumableCost($product, $qty),
            ];
        }

        $summaries = collect($rows)
            ->groupBy(fn (array $row) => $row['product']->category?->category_name ?? 'Uncategorized')
            ->map(function ($items): array {
                $omzet = (float) collect($items)->sum('total');
                $petugasCommission = (float) collect($items)->sum('commission');
                $barberCommission = (float) collect($items)->sum('barber_share');
                $consumableCost = (float) collect($items)->sum('consumable_cost');

                return [
                    'omzet' => $omzet,
                    'petugas_commission' => $petugasCommission,
                    'petugas_rate' => $omzet > 0 ? ($petugasCommission / $omzet) : 0,
                    'barber_commission' => $barberCommission,
                    'barber_rate' => $omzet > 0 ? ($barberCommission / $omzet) : 0,
                    'consumable_cost' => $consumableCost,
                ];
            })
            ->toArray();

        return [
            'employee' => $employee,
            'start' => $start,
            'end' => $end,
            'rows' => $rows,
            'summaries' => $summaries,
        ];
    }

    protected function calculateConsumableCost(Product $serviceProduct, int $qtySold): float
    {
        if ($qtySold <= 0) {
            return 0.0;
        }

        $unitCost = 0.0;

        foreach ($serviceProduct->consumables as $mapping) {
            $consumableCost = (float) ($mapping->consumableProduct?->cost_price ?? 0);
            $unitCost += $consumableCost * (int) $mapping->qty_per_unit;
        }

        return $unitCost * $qtySold;
    }

    protected function applyEmployeeServiceScope(Builder $query, Employee $employee): Builder
    {
        return match ($employee->role) {
            'barber' => $query->whereHas('category', function (Builder $categoryQuery): void {
                $categoryQuery->whereRaw('LOWER(category_name) LIKE ?', ['%barber%']);
            }),
            'reflexology' => $query->whereHas('category', function (Builder $categoryQuery): void {
                $categoryQuery->whereRaw('LOWER(category_name) LIKE ?', ['%reflex%']);
            }),
            default => $query,
        };
    }
}
