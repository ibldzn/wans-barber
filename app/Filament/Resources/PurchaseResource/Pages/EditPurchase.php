<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use App\Services\PurchaseService;
use Filament\Resources\Pages\EditRecord;

class EditPurchase extends EditRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $items = $data['items'] ?? [];

        PurchaseResource::validatePurchasableItems($items);
        $data['total_amount'] = collect($items)->sum('subtotal');

        return $data;
    }

    protected function afterSave(): void
    {
        app(PurchaseService::class)->syncInventoryAndLedger($this->record);
    }
}
