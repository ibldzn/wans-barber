<?php

namespace App\Filament\Resources\CashExpenseResource\Pages;

use App\Filament\Resources\CashExpenseResource;
use App\Models\PaymentMethod;
use Filament\Resources\Pages\CreateRecord;

class CreateCashExpense extends CreateRecord
{
    protected static string $resource = CashExpenseResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'expense';
        $data['payment_method_id'] = PaymentMethod::query()->where('method_name', 'Cash')->value('id');
        $data['created_by'] = auth()->id();

        return $data;
    }
}
