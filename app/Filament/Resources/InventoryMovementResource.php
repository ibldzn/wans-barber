<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryMovementResource\Pages;
use App\Models\InventoryMovement;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InventoryMovementResource extends Resource
{
    protected static ?string $model = InventoryMovement::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Pergerakan Stok')->schema([
                Select::make('product_id')
                    ->label('Produk')
                    ->relationship('product', 'product_name')
                    ->searchable()
                    ->required(),
                Select::make('type')
                    ->label('Tipe')
                    ->options([
                        'in' => 'In',
                        'out' => 'Out',
                        'adjustment' => 'Adjustment',
                    ])
                    ->required(),
                TextInput::make('qty')
                    ->label('Qty')
                    ->integer()
                    ->required(),
                TextInput::make('unit_cost')
                    ->label('Harga Modal')
                    ->numeric(),
                DateTimePicker::make('occurred_at')
                    ->label('Tanggal')
                    ->default(now())
                    ->required(),
                TextInput::make('notes')
                    ->label('Catatan'),
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
                TextColumn::make('product.product_name')->label('Produk')->searchable(),
                TextColumn::make('type')->label('Tipe')->badge(),
                TextColumn::make('qty')->label('Qty'),
                TextColumn::make('unit_cost')->label('Harga Modal')->money('IDR'),
                TextColumn::make('occurred_at')->label('Tanggal')->dateTime(),
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
            'index' => Pages\ListInventoryMovements::route('/'),
            'create' => Pages\CreateInventoryMovement::route('/create'),
            'edit' => Pages\EditInventoryMovement::route('/{record}/edit'),
        ];
    }
}
