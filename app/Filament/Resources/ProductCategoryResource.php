<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductCategoryResource\Pages;
use App\Models\ProductCategory;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductCategoryResource extends Resource
{
    protected static ?string $model = ProductCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Kategori Produk';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Kategori Produk')->schema([
                TextInput::make('category_name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('category_description')
                    ->label('Deskripsi')
                    ->maxLength(255),
                Select::make('category_type')
                    ->label('Tipe')
                    ->options([
                        'service' => 'Service',
                        'retail' => 'Retail',
                        'consumable' => 'Consumable',
                    ])
                    ->required(),
                TextInput::make('commission_rate_regular')
                    ->label('Komisi Reguler')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(1),
                TextInput::make('commission_rate_callout')
                    ->label('Komisi Panggilan')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(1),
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
                TextColumn::make('category_name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('category_type')->label('Tipe')->badge(),
                TextColumn::make('commission_rate_regular')->label('Komisi Reguler')->formatStateUsing(fn($state) => number_format($state * 100, 0) . '%'),
                TextColumn::make('commission_rate_callout')->label('Komisi Panggilan')->formatStateUsing(fn($state) => number_format($state * 100, 0) . '%'),
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
            'index' => Pages\ListProductCategories::route('/'),
            'create' => Pages\CreateProductCategory::route('/create'),
            'edit' => Pages\EditProductCategory::route('/{record}/edit'),
        ];
    }
}
