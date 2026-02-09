<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
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

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Produk')->schema([
                Select::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'category_name')
                    ->preload()
                    ->searchable()
                    ->required(),
                TextInput::make('product_name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('product_description')
                    ->label('Deskripsi')
                    ->maxLength(255),
                Select::make('product_type')
                    ->label('Tipe')
                    ->options([
                        'service' => 'Service',
                        'retail' => 'Retail',
                        'consumable' => 'Consumable',
                    ])
                    ->required(),
                TextInput::make('product_price')
                    ->label('Harga Reguler')
                    ->numeric()
                    ->required(),
                TextInput::make('product_price_other')
                    ->label('Harga Panggilan')
                    ->numeric(),
                Toggle::make('track_stock')
                    ->label('Track Stok')
                    ->default(false),
                TextInput::make('cost_price')
                    ->label('Harga Modal')
                    ->numeric(),
                TextInput::make('reorder_level')
                    ->label('Reorder Level')
                    ->numeric(),
                TextInput::make('commission_rate_override_regular')
                    ->label('Override Komisi Reguler')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(1),
                TextInput::make('commission_rate_override_callout')
                    ->label('Override Komisi Panggilan')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(1),
                Toggle::make('is_active')
                    ->label('Aktif')
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
                TextColumn::make('product_name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('category.category_name')->label('Kategori')->sortable(),
                TextColumn::make('product_type')->label('Tipe')->badge(),
                TextColumn::make('product_price')->label('Harga')->money('IDR'),
                TextColumn::make('product_price_other')->label('Harga Panggilan')->money('IDR'),
                TextColumn::make('current_stock')
                    ->label('Stok')
                    ->getStateUsing(function (Product $record): float {
                        $in = $record->inventoryMovements()->where('type', 'in')->sum('qty');
                        $out = $record->inventoryMovements()->where('type', 'out')->sum('qty');
                        $adjust = $record->inventoryMovements()->where('type', 'adjustment')->sum('qty');
                        return ($in - $out) + $adjust;
                    }),
                ToggleColumn::make('is_active')->label('Aktif'),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
