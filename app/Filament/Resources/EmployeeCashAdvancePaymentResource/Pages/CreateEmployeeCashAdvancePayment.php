<?php

namespace App\Filament\Resources\EmployeeCashAdvancePaymentResource\Pages;

use App\Filament\Resources\EmployeeCashAdvancePaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeCashAdvancePayment extends CreateRecord
{
    protected static string $resource = EmployeeCashAdvancePaymentResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $cashAdvanceId = (int) ($data['employee_cash_advance_id'] ?? 0);
        $amount = (float) ($data['amount'] ?? 0);

        EmployeeCashAdvancePaymentResource::validatePaymentAmount($cashAdvanceId, $amount);

        return $data;
    }

    protected function afterCreate(): void
    {
        EmployeeCashAdvancePaymentResource::syncCashAdvanceStatusById(
            (int) $this->record->employee_cash_advance_id,
        );
    }
}
