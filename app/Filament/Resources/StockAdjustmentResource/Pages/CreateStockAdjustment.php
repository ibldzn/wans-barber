<?php

namespace App\Filament\Resources\StockAdjustmentResource\Pages;

use App\Filament\Resources\StockAdjustmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStockAdjustment extends CreateRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    /**
     * @param array<string, mixed> $data
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'adjustment';
        $data['reference_type'] = null;
        $data['reference_id'] = null;

        return $data;
    }
}
