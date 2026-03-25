<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->isAdmin() || $user->isKasir();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Low Stock')
            ->description('Produk aktif yang stoknya sudah menyentuh reorder level.')
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('product_name')
                    ->label('Produk')
                    ->searchable(),
                TextColumn::make('category.category_name')
                    ->label('Kategori')
                    ->placeholder('-'),
                TextColumn::make('current_stock')
                    ->label('Stok')
                    ->badge()
                    ->color(fn (Product $record): string => ((int) $record->current_stock <= 0) ? 'danger' : 'warning'),
                TextColumn::make('reorder_level')
                    ->label('Reorder Level'),
                TextColumn::make('shortage')
                    ->label('Kekurangan')
                    ->state(fn (Product $record): int => max(0, ((int) $record->reorder_level) - ((int) $record->current_stock)))
                    ->badge()
                    ->color('danger'),
            ])
            ->paginated([5, 10, 25]);
    }

    protected function getTableQuery(): Builder
    {
        $stockExpression = <<<SQL
            (
                COALESCE((
                    SELECT SUM(inventory_movements.qty)
                    FROM inventory_movements
                    WHERE inventory_movements.product_id = products.id
                    AND inventory_movements.type = 'in'
                ), 0)
                - COALESCE((
                    SELECT SUM(inventory_movements.qty)
                    FROM inventory_movements
                    WHERE inventory_movements.product_id = products.id
                    AND inventory_movements.type = 'out'
                ), 0)
                + COALESCE((
                    SELECT SUM(inventory_movements.qty)
                    FROM inventory_movements
                    WHERE inventory_movements.product_id = products.id
                    AND inventory_movements.type = 'adjustment'
                ), 0)
            )
        SQL;

        return Product::query()
            ->with('category')
            ->select('products.*')
            ->selectRaw("{$stockExpression} as current_stock")
            ->where('products.is_active', true)
            ->where('products.track_stock', true)
            ->whereNotNull('products.reorder_level')
            ->where('products.reorder_level', '>', 0)
            ->whereRaw("{$stockExpression} <= products.reorder_level")
            ->orderByRaw("{$stockExpression} ASC");
    }
}
