<?php

namespace App\Filament\Resources\CashExpenseResource\Pages;

use App\Filament\Resources\CashExpenseResource;
use App\Models\PaymentMethod;
use Filament\Resources\Pages\EditRecord;

class EditCashExpense extends EditRecord
{
    protected static string $resource = CashExpenseResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['type'] = 'expense';
        $data['payment_method_id'] = PaymentMethod::query()->where('method_name', 'Cash')->value('id');

        return $data;
    }
}
