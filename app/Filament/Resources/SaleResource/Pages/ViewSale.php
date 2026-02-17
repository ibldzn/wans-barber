<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

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
}
