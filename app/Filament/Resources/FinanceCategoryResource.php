<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinanceCategoryResource\Pages;
use App\Models\FinanceCategory;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class FinanceCategoryResource extends Resource
{
    protected static ?string $model = FinanceCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Kategori Keuangan';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Kategori Keuangan')->schema([
                TextInput::make('name')->label('Nama')->required(),
                Select::make('type')
                    ->label('Tipe')
                    ->options([
                        'income' => 'Income',
                        'expense' => 'Expense',
                    ])
                    ->required(),
                Toggle::make('is_system')->label('System')->default(false),
                TextInput::make('description')->label('Deskripsi'),
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
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('type')->label('Tipe')->badge(),
                ToggleColumn::make('is_system')->label('System'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinanceCategories::route('/'),
            'create' => Pages\CreateFinanceCategory::route('/create'),
            'edit' => Pages\EditFinanceCategory::route('/{record}/edit'),
        ];
    }
}
