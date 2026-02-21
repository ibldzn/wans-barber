<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashExpenseResource\Pages;
use App\Models\FinanceCategory;
use App\Models\FinancialTransaction;
use App\Models\PaymentMethod;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CashExpenseResource extends Resource
{
    protected static ?string $model = FinancialTransaction::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Pengeluaran Harian Kasir';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Pengeluaran Harian')->schema([
                Hidden::make('type')->default('expense')->dehydrated(true),
                Hidden::make('payment_method_id')
                    ->default(fn () => static::getCashPaymentMethodId())
                    ->dehydrated(true),
                Select::make('category_id')
                    ->label('Kategori Pengeluaran')
                    ->options(fn () => FinanceCategory::query()->where('type', 'expense')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Placeholder::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->content('Cash (otomatis)'),
                TextInput::make('amount')
                    ->label('Nominal')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                DateTimePicker::make('occurred_at')
                    ->label('Tanggal')
                    ->default(now())
                    ->required(),
                TextInput::make('description')
                    ->label('Deskripsi')
                    ->required(),
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
                TextColumn::make('category.name')->label('Kategori')->searchable(),
                TextColumn::make('amount')->label('Nominal')->money('IDR'),
                TextColumn::make('occurred_at')->label('Tanggal')->dateTime(),
                TextColumn::make('description')->label('Deskripsi')->wrap(),
                TextColumn::make('creator.name')->label('Input Oleh')->toggleable(),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $cashMethodId = static::getCashPaymentMethodId();

        if (! $cashMethodId) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()
            ->where('type', 'expense')
            ->where('payment_method_id', $cashMethodId);
    }

    public static function canViewAny(): bool
    {
        return static::canAccessByRole() && (bool) static::getCashPaymentMethodId();
    }

    public static function canCreate(): bool
    {
        return static::canAccessByRole() && (bool) static::getCashPaymentMethodId();
    }

    public static function canEdit($record): bool
    {
        return static::canAccessByRole() && (bool) static::getCashPaymentMethodId();
    }

    public static function canDelete($record): bool
    {
        return static::canAccessByRole() && (bool) static::getCashPaymentMethodId();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashExpenses::route('/'),
            'create' => Pages\CreateCashExpense::route('/create'),
            'edit' => Pages\EditCashExpense::route('/{record}/edit'),
        ];
    }

    protected static function getCashPaymentMethodId(): ?int
    {
        return PaymentMethod::query()->where('method_name', 'Cash')->value('id');
    }

    protected static function canAccessByRole(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->isAdmin() || $user->isKasir();
    }
}
