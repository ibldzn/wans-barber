<?php

namespace App\Filament\Resources\EmployeeCashAdvancePaymentResource\Pages;

use App\Filament\Resources\EmployeeCashAdvancePaymentResource;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeCashAdvancePayment extends EditRecord
{
    protected static string $resource = EmployeeCashAdvancePaymentResource::class;

    protected ?int $previousCashAdvanceId = null;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->previousCashAdvanceId = (int) $this->record->employee_cash_advance_id;

        $cashAdvanceId = (int) ($data['employee_cash_advance_id'] ?? 0);
        $amount = (float) ($data['amount'] ?? 0);

        EmployeeCashAdvancePaymentResource::validatePaymentAmount(
            cashAdvanceId: $cashAdvanceId,
            amount: $amount,
            excludePaymentId: (int) $this->record->id,
        );

        return $data;
    }

    protected function afterSave(): void
    {
        EmployeeCashAdvancePaymentResource::syncCashAdvanceStatusById(
            (int) $this->record->employee_cash_advance_id,
        );

        if ($this->previousCashAdvanceId !== null
            && $this->previousCashAdvanceId !== (int) $this->record->employee_cash_advance_id) {
            EmployeeCashAdvancePaymentResource::syncCashAdvanceStatusById($this->previousCashAdvanceId);
        }
    }
}
