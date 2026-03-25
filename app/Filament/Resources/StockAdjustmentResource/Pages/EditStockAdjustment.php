<?php

namespace App\Filament\Resources\StockAdjustmentResource\Pages;

use App\Filament\Resources\StockAdjustmentResource;
use Filament\Resources\Pages\EditRecord;

class EditStockAdjustment extends EditRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    /**
     * @param array<string, mixed> $data
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['type'] = 'adjustment';
        $data['reference_type'] = null;
        $data['reference_id'] = null;

        return $data;
    }
}
