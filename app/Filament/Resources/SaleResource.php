<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-refund';

    protected static string|\UnitEnum|null $navigationGroup = 'POS';

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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_no')->label('Invoice')->searchable()->sortable(),
                TextColumn::make('cashier.emp_name')->label('Kasir'),
                TextColumn::make('paymentMethod.method_name')->label('Metode'),
                TextColumn::make('total')->label('Total')->money('IDR'),
                TextColumn::make('paid_at')->label('Tanggal')->dateTime(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Detail')
                    ->visible(fn (Sale $record): bool => static::canView($record))
                    ->url(fn (Sale $record): string => static::getUrl('view', ['record' => $record])),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Invoice')->schema([
                TextEntry::make('invoice_no')->label('Invoice'),
                TextEntry::make('paid_at')->label('Tanggal')->dateTime(),
                TextEntry::make('cashier.emp_name')->label('Kasir'),
                TextEntry::make('paymentMethod.method_name')->label('Metode'),
                TextEntry::make('customer_name')->label('Customer')->placeholder('-'),
                TextEntry::make('customer_phone')->label('No HP')->placeholder('-'),
            ])->columns(2),
            Section::make('Items')->schema([
                RepeatableEntry::make('items')
                    ->table([
                        TableColumn::make('Produk'),
                        TableColumn::make('Pegawai'),
                        TableColumn::make('Qty')->alignment('right'),
                        TableColumn::make('Harga')->alignment('right'),
                        TableColumn::make('Total')->alignment('right'),
                    ])
                    ->schema([
                        TextEntry::make('product.product_name')->hiddenLabel(),
                        TextEntry::make('employee.emp_name')->hiddenLabel()->placeholder('-'),
                        TextEntry::make('qty')->hiddenLabel(),
                        TextEntry::make('unit_price')->hiddenLabel()->money('IDR'),
                        TextEntry::make('line_total')->hiddenLabel()->money('IDR'),
                    ])
                    ->columnSpanFull(),
            ]),
            Section::make('Ringkasan')->schema([
                TextEntry::make('subtotal')->label('Subtotal')->money('IDR'),
                TextEntry::make('discount')->label('Diskon')->money('IDR'),
                TextEntry::make('total')->label('Total')->money('IDR'),
                TextEntry::make('notes')->label('Catatan')->placeholder('-'),
            ])->columns(2),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'view' => Pages\ViewSale::route('/{record}'),
        ];
    }
}
