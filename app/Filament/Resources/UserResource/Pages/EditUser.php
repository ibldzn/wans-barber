<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected ?int $previousEmployeeId = null;

    /**
     * @param array<string, mixed> $data
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['role'] = $this->record->roles->first()?->name ?? $data['role'] ?? 'admin';

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->previousEmployeeId = $this->record->employee_id;

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        UserResource::syncUserAccess($this->record, $this->previousEmployeeId);
    }
}
