<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?int $previousEmployeeId = null;

    /**
     * @param array<string, mixed> $data
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->previousEmployeeId = null;

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        UserResource::syncUserAccess($this->record, $this->previousEmployeeId);
    }
}
