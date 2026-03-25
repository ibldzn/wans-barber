<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\SaleItem;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    protected static ?string $title = 'Detail Penjualan';

    protected string $view = 'filament.resources.sale-resource.pages.view-sale';

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->record->loadMissing([
            'cashier',
            'paymentMethod',
            'creator',
            'items.product.category',
            'items.employee',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printThermal')
                ->label('Print 58mm')
                ->icon('heroicon-o-printer')
                ->url(fn(): string => route('sales.print.thermal', $this->record))
                ->openUrlInNewTab(),
            Action::make('downloadEscPos')
                ->label('ESC/POS RAW')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn(): string => route('sales.print.thermal', ['sale' => $this->record, 'raw' => 1]))
                ->openUrlInNewTab(),
        ];
    }

    public function formatCurrency(float | int | string | null $amount): string
    {
        return 'Rp ' . number_format((float) $amount, 0, ',', '.');
    }

    public function getTotalQuantity(): int
    {
        return (int) $this->record->items->sum('qty');
    }

    public function getLineCount(): int
    {
        return $this->record->items->count();
    }

    public function getServiceLineCount(): int
    {
        return $this->record->items
            ->filter(fn (SaleItem $item): bool => $item->product?->product_type === 'service')
            ->count();
    }

    public function isAutoConsumable(SaleItem $item): bool
    {
        return $item->product?->product_type === 'consumable'
            && blank($item->employee_id)
            && str_starts_with((string) $item->notes, 'Auto consumable dari ');
    }

    public function getItemKindLabel(SaleItem $item): string
    {
        if ($this->isAutoConsumable($item)) {
            return 'Consumable Auto';
        }

        return match ($item->product?->product_type) {
            'service' => 'Service',
            'retail' => 'Retail',
            'consumable' => 'Consumable',
            default => 'Item',
        };
    }
}
