<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockAdjustmentResource\Pages;
use App\Filament\Support\HasSafeDeleteActions;
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
use Illuminate\Database\Eloquent\Builder;

class StockAdjustmentResource extends Resource
{
    use HasSafeDeleteActions;

    protected static ?string $model = InventoryMovement::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Penyesuaian Stok';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Penyesuaian Stok')->schema([
                Select::make('product_id')
                    ->label('Produk')
                    ->relationship(
                        'product',
                        'product_name',
                        modifyQueryUsing: fn (Builder $query) => PurchaseResource::scopePurchasableProducts($query)
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('qty')
                    ->label('Qty Adjustment')
                    ->integer()
                    ->helperText('Isi positif untuk tambah stok, negatif untuk kurangi stok.')
                    ->rules(['integer', 'not_in:0'])
                    ->required(),
                TextInput::make('unit_cost')
                    ->label('Harga Modal')
                    ->numeric(),
                DateTimePicker::make('occurred_at')
                    ->label('Tanggal')
                    ->default(now())
                    ->required(),
                TextInput::make('notes')
                    ->label('Catatan')
                    ->required()
                    ->maxLength(255),
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
                TextColumn::make('qty')
                    ->label('Qty Adjustment')
                    ->badge()
                    ->color(fn (InventoryMovement $record): string => $record->qty > 0 ? 'success' : 'danger'),
                TextColumn::make('unit_cost')->label('Harga Modal')->money('IDR')->placeholder('-'),
                TextColumn::make('occurred_at')->label('Tanggal')->dateTime(),
                TextColumn::make('notes')->label('Catatan')->wrap(),
            ])
            ->defaultSort('occurred_at', 'desc')
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNull('reference_type')
            ->where('type', 'adjustment');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockAdjustments::route('/'),
            'create' => Pages\CreateStockAdjustment::route('/create'),
            'edit' => Pages\EditStockAdjustment::route('/{record}/edit'),
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

    protected static function canAccessByRole(): bool
    {
        $user = auth()->user();

        return (bool) ($user && $user->isAdmin());
    }
}
