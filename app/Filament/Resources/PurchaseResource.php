<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages;
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

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

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
                            ->relationship('product', 'product_name')
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
}
