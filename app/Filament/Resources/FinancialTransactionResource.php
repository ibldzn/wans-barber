<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinancialTransactionResource\Pages;
use App\Models\FinancialTransaction;
use App\Models\Payslip;
use App\Models\Purchase;
use App\Models\Sale;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FinancialTransactionResource extends Resource
{
    protected static ?string $model = FinancialTransaction::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Ledger Keuangan';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->label('Tipe')->badge(),
                TextColumn::make('category.name')->label('Kategori')->searchable(),
                TextColumn::make('sub_category')->label('Subkategori')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('amount')->label('Nominal')->money('IDR'),
                TextColumn::make('paymentMethod.method_name')->label('Metode'),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->state(fn (FinancialTransaction $record): string => static::getReferenceLabel($record->reference_type)),
                TextColumn::make('reference_id')
                    ->label('Referensi')
                    ->state(fn (FinancialTransaction $record): string => static::getReferenceCode($record))
                    ->placeholder('-'),
                TextColumn::make('occurred_at')->label('Tanggal')->dateTime(),
                TextColumn::make('description')->label('Deskripsi')->wrap()->toggleable(),
            ])
            ->defaultSort('occurred_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinancialTransactions::route('/'),
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
            Payslip::class => 'Payroll',
            null => 'Manual',
            default => class_basename((string) $referenceType),
        };
    }

    protected static function getReferenceCode(FinancialTransaction $record): string
    {
        if (blank($record->reference_type) || blank($record->reference_id)) {
            return 'Manual';
        }

        return '#' . $record->reference_id;
    }
}
