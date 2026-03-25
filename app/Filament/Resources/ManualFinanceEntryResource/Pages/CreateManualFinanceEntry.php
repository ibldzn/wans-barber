<?php

namespace App\Filament\Resources\ManualFinanceEntryResource\Pages;

use App\Filament\Resources\ManualFinanceEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateManualFinanceEntry extends CreateRecord
{
    protected static string $resource = ManualFinanceEntryResource::class;

    /**
     * @param array<string, mixed> $data
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['reference_type'] = null;
        $data['reference_id'] = null;
        $data['created_by'] = auth()->id();

        return $data;
    }
}
