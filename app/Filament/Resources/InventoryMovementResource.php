<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryMovementResource\Pages;
use App\Models\InventoryMovement;
use App\Models\Purchase;
use App\Models\Sale;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InventoryMovementResource extends Resource
{
    protected static ?string $model = InventoryMovement::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Log Pergerakan Stok';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.product_name')->label('Produk')->searchable(),
                TextColumn::make('type')->label('Tipe')->badge(),
                TextColumn::make('qty')->label('Qty'),
                TextColumn::make('unit_cost')->label('Harga Modal')->money('IDR'),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->state(fn (InventoryMovement $record): string => static::getReferenceLabel($record->reference_type)),
                TextColumn::make('reference_id')
                    ->label('Referensi')
                    ->state(fn (InventoryMovement $record): string => static::getReferenceCode($record))
                    ->placeholder('-'),
                TextColumn::make('occurred_at')->label('Tanggal')->dateTime(),
                TextColumn::make('notes')->label('Catatan')->wrap()->toggleable(),
            ])
            ->defaultSort('occurred_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryMovements::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    protected static function getReferenceLabel(?string $referenceType): string
    {
        return match ($referenceType) {
            Sale::class => 'Penjualan',
            Purchase::class => 'Pembelian',
            null => 'Adjustment Manual',
            default => class_basename((string) $referenceType),
        };
    }

    protected static function getReferenceCode(InventoryMovement $record): string
    {
        if (blank($record->reference_type) || blank($record->reference_id)) {
            return 'Manual';
        }

        return '#' . $record->reference_id;
    }
}
