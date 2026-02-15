<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductConsumableResource\Pages;
use App\Models\Product;
use App\Models\ProductConsumable;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductConsumableResource extends Resource
{
    protected static ?string $model = ProductConsumable::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Konsumsi Bahan';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Konsumsi Bahan')->schema([
                Select::make('service_product_id')
                    ->label('Produk Jasa')
                    ->options(fn() => Product::where('product_type', 'service')->pluck('product_name', 'id'))
                    ->searchable()
                    ->required(),
                Select::make('consumable_product_id')
                    ->label('Produk Consumable')
                    ->options(fn() => Product::where('product_type', 'consumable')->pluck('product_name', 'id'))
                    ->searchable()
                    ->required(),
                TextInput::make('qty_per_unit')
                    ->label('Qty per Jasa')
                    ->integer()
                    ->default(1),
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
                TextColumn::make('serviceProduct.product_name')->label('Jasa')->sortable()->searchable(),
                TextColumn::make('consumableProduct.product_name')->label('Consumable')->sortable()->searchable(),
                TextColumn::make('qty_per_unit')->label('Qty per Jasa'),
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
            'index' => Pages\ListProductConsumables::route('/'),
            'create' => Pages\CreateProductConsumable::route('/create'),
            'edit' => Pages\EditProductConsumable::route('/{record}/edit'),
        ];
    }
}
