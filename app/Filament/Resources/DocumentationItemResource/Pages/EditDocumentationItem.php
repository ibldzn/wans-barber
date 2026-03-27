<?php

namespace App\Filament\Resources\DocumentationItemResource\Pages;

use App\Filament\Resources\DocumentationItemResource;
use Filament\Resources\Pages\EditRecord;

class EditDocumentationItem extends EditRecord
{
    protected static string $resource = DocumentationItemResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return DocumentationItemResource::mutateFormDataBeforeFill($data, $this->record);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return DocumentationItemResource::mutateFormDataBeforePersist($data);
    }
}
