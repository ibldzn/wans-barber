<?php

namespace App\Filament\Resources\DocumentationItemResource\Pages;

use App\Filament\Resources\DocumentationItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumentationItem extends CreateRecord
{
    protected static string $resource = DocumentationItemResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return DocumentationItemResource::mutateFormDataBeforePersist($data);
    }
}
