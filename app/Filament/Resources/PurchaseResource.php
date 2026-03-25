<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages;
use App\Models\Product;
use App\Models\Purchase;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Pembelian';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Pembelian')->schema([
                Select::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
                DatePicker::make('purchased_at')
                    ->label('Tanggal')
                    ->required()
                    ->default(now()),
                TextInput::make('notes')
                    ->label('Catatan'),
            ])
                ->columnSpanFull()
                ->inlineLabel()
                ->columns(1),
            Section::make('Items')->schema([
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Select::make('product_id')
                            ->label('Produk')
                            ->relationship(
                                'product',
                                'product_name',
                                modifyQueryUsing: fn (Builder $query) => static::scopePurchasableProducts($query)
                            )
                            ->preload()
                            ->searchable()
                            ->required(),
                        TextInput::make('qty')
                            ->label('Qty')
                            ->numeric()
                            ->default(1)
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                $qty = (float) ($get('qty') ?? 0);
                                $unit = (float) ($get('unit_cost') ?? 0);
                                $set('subtotal', $qty * $unit);
                            })
                            ->required(),
                        TextInput::make('unit_cost')
                            ->label('Harga Satuan')
                            ->numeric()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                $qty = (float) ($get('qty') ?? 0);
                                $unit = (float) ($get('unit_cost') ?? 0);
                                $set('subtotal', $qty * $unit);
                            })
                            ->required(),
                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                    ])
                    ->columns(4)
                    ->defaultItems(1)
                    ->addActionLabel('Tambah Item'),
            ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('supplier.name')->label('Supplier'),
                TextColumn::make('purchased_at')->label('Tanggal')->date(),
                TextColumn::make('total_amount')->label('Total')->money('IDR'),
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
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'edit' => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }

    public static function validatePurchasableItems(array $items): void
    {
        $errors = [];

        foreach ($items as $index => $item) {
            $productId = (int) ($item['product_id'] ?? 0);

            if ($productId <= 0) {
                continue;
            }

            $product = Product::query()->find($productId);

            if (! $product) {
                $errors["items.{$index}.product_id"] = 'Produk tidak ditemukan.';
                continue;
            }

            if (! static::isPurchasableProduct($product)) {
                $errors["items.{$index}.product_id"] = 'Produk service atau non-stock tidak bisa dibeli lewat modul pembelian.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public static function scopePurchasableProducts(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('track_stock', true)
            ->whereIn('product_type', ['retail', 'consumable']);
    }

    public static function isPurchasableProduct(Product $product): bool
    {
        return $product->is_active
            && $product->track_stock
            && in_array($product->product_type, ['retail', 'consumable'], true);
    }
}
