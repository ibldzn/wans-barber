<?php

namespace App\Filament\Pages;

use App\Models\DocumentationItem;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

class DocumentationPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Dokumentasi';

    protected static ?string $title = 'Dokumentasi';

    protected static ?string $navigationLabel = 'Dokumentasi';

    protected static ?string $slug = 'dokumentasi';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.documentation-page';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->isAdmin() || $user->isKasir()));
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        return [
            'items' => DocumentationItem::query()
                ->visible()
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get(),
            'canRevealSecrets' => (bool) auth()->user()?->isAdmin() || (bool) auth()->user()?->isKasir(),
        ];
    }
}
