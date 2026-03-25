<?php

namespace App\Filament\Resources\ManualFinanceEntryResource\Pages;

use App\Filament\Resources\ManualFinanceEntryResource;
use Filament\Resources\Pages\EditRecord;

class EditManualFinanceEntry extends EditRecord
{
    protected static string $resource = ManualFinanceEntryResource::class;

    /**
     * @param array<string, mixed> $data
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['reference_type'] = null;
        $data['reference_id'] = null;

        return $data;
    }
}
