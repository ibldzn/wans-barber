<?php

namespace App\Filament\Resources\PayrollPeriodResource\Pages;

use App\Filament\Resources\PayrollPeriodResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreatePayrollPeriod extends CreateRecord
{
    protected static string $resource = PayrollPeriodResource::class;

    protected function afterValidate(): void
    {
        try {
            PayrollPeriodResource::validatePeriodRules($this->data ?? [], statePath: 'data');
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('Periode payroll tidak valid')
                ->body(collect($exception->errors())->flatten()->join("\n"))
                ->danger()
                ->send();

            throw $exception;
        }
    }
}
