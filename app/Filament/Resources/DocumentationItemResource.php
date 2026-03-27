<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentationItemResource\Pages;
use App\Filament\Support\HasSafeDeleteActions;
use App\Models\DocumentationItem;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class DocumentationItemResource extends Resource
{
    use HasSafeDeleteActions;

    protected static ?string $model = DocumentationItem::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Dokumentasi';

    protected static ?string $navigationLabel = 'Item Dokumentasi';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Item Dokumentasi')->schema([
                TextInput::make('label')
                    ->label('Label')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                        if (blank($get('key')) && filled($state)) {
                            $set('key', Str::slug($state));
                        }
                    }),
                TextInput::make('key')
                    ->label('Key')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Identifier internal unik, contoh: nomor-wifi')
                    ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'),
                Select::make('field_type')
                    ->label('Tipe Data')
                    ->options(DocumentationItem::getFieldTypeOptions())
                    ->default(DocumentationItem::FIELD_TYPE_TEXT)
                    ->live()
                    ->required(),
                TextInput::make('value_text')
                    ->label('Value')
                    ->visible(fn (Get $get): bool => in_array($get('field_type'), [
                        DocumentationItem::FIELD_TYPE_TEXT,
                        DocumentationItem::FIELD_TYPE_PHONE,
                    ], true))
                    ->maxLength(65535),
                TextInput::make('value_email')
                    ->label('Value')
                    ->email()
                    ->visible(fn (Get $get): bool => $get('field_type') === DocumentationItem::FIELD_TYPE_EMAIL),
                TextInput::make('value_url')
                    ->label('Value')
                    ->url()
                    ->visible(fn (Get $get): bool => $get('field_type') === DocumentationItem::FIELD_TYPE_URL),
                TextInput::make('value_number')
                    ->label('Value')
                    ->numeric()
                    ->visible(fn (Get $get): bool => $get('field_type') === DocumentationItem::FIELD_TYPE_NUMBER),
                DatePicker::make('value_date')
                    ->label('Value')
                    ->visible(fn (Get $get): bool => $get('field_type') === DocumentationItem::FIELD_TYPE_DATE),
                Textarea::make('value_multiline')
                    ->label('Value')
                    ->rows(5)
                    ->visible(fn (Get $get): bool => $get('field_type') === DocumentationItem::FIELD_TYPE_MULTILINE),
                TextInput::make('value_secret')
                    ->label('Value')
                    ->password()
                    ->revealable()
                    ->visible(fn (Get $get): bool => $get('field_type') === DocumentationItem::FIELD_TYPE_SECRET)
                    ->helperText('Nilai akan disimpan terenkripsi di database.'),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(3),
                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_visible')
                    ->label('Tampilkan di halaman Dokumentasi')
                    ->default(true),
            ])
                ->columnSpanFull()
                ->inlineLabel()
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label('Label')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('key')
                    ->label('Key')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('field_type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => DocumentationItem::getFieldTypeOptions()[$state] ?? $state),
                TextColumn::make('value_preview')
                    ->label('Preview')
                    ->state(fn (DocumentationItem $record): string => $record->getPreviewValue())
                    ->wrap(),
                IconColumn::make('is_visible')
                    ->label('Visible')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                static::makeDeleteAction(),
            ])
            ->toolbarActions([
                static::makeDeleteBulkAction(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocumentationItems::route('/'),
            'create' => Pages\CreateDocumentationItem::route('/create'),
            'edit' => Pages\EditDocumentationItem::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return static::canAccessByRole();
    }

    public static function canCreate(): bool
    {
        return static::canAccessByRole();
    }

    public static function canEdit($record): bool
    {
        return static::canAccessByRole();
    }

    public static function canDelete($record): bool
    {
        return static::canAccessByRole();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mutateFormDataBeforePersist(array $data): array
    {
        $fieldType = (string) ($data['field_type'] ?? DocumentationItem::FIELD_TYPE_TEXT);

        $plainValue = match ($fieldType) {
            DocumentationItem::FIELD_TYPE_MULTILINE => $data['value_multiline'] ?? null,
            DocumentationItem::FIELD_TYPE_DATE => $data['value_date'] ?? null,
            DocumentationItem::FIELD_TYPE_NUMBER => $data['value_number'] ?? null,
            DocumentationItem::FIELD_TYPE_EMAIL => $data['value_email'] ?? null,
            DocumentationItem::FIELD_TYPE_URL => $data['value_url'] ?? null,
            DocumentationItem::FIELD_TYPE_SECRET => $data['value_secret'] ?? null,
            default => $data['value_text'] ?? null,
        };

        $data['value'] = blank($plainValue)
            ? null
            : ($fieldType === DocumentationItem::FIELD_TYPE_SECRET
                ? Crypt::encryptString((string) $plainValue)
                : (string) $plainValue);

        unset(
            $data['value_text'],
            $data['value_email'],
            $data['value_url'],
            $data['value_number'],
            $data['value_date'],
            $data['value_multiline'],
            $data['value_secret'],
        );

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mutateFormDataBeforeFill(array $data, DocumentationItem $record): array
    {
        $plainValue = $record->getPlainValue();

        $data['value_text'] = in_array($record->field_type, [
            DocumentationItem::FIELD_TYPE_TEXT,
            DocumentationItem::FIELD_TYPE_PHONE,
        ], true) ? $plainValue : null;
        $data['value_email'] = $record->field_type === DocumentationItem::FIELD_TYPE_EMAIL ? $plainValue : null;
        $data['value_url'] = $record->field_type === DocumentationItem::FIELD_TYPE_URL ? $plainValue : null;
        $data['value_number'] = $record->field_type === DocumentationItem::FIELD_TYPE_NUMBER ? $plainValue : null;
        $data['value_date'] = $record->field_type === DocumentationItem::FIELD_TYPE_DATE ? $plainValue : null;
        $data['value_multiline'] = $record->field_type === DocumentationItem::FIELD_TYPE_MULTILINE ? $plainValue : null;
        $data['value_secret'] = $record->field_type === DocumentationItem::FIELD_TYPE_SECRET ? $plainValue : null;

        return $data;
    }

    protected static function canAccessByRole(): bool
    {
        $user = auth()->user();

        return (bool) ($user && $user->isAdmin());
    }
}
