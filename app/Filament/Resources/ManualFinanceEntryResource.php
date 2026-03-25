<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ManualFinanceEntryResource\Pages;
use App\Filament\Support\HasSafeDeleteActions;
use App\Models\FinancialTransaction;
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

class ManualFinanceEntryResource extends Resource
{
    use HasSafeDeleteActions;

    protected static ?string $model = FinancialTransaction::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Transaksi Manual';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Transaksi Manual')->schema([
                Select::make('type')
                    ->label('Tipe')
                    ->options([
                        'income' => 'Income',
                        'expense' => 'Expense',
                    ])
                    ->required(),
                Select::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->required(),
                TextInput::make('sub_category')
                    ->label('Subkategori')
                    ->maxLength(255),
                TextInput::make('amount')
                    ->label('Nominal')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                Select::make('payment_method_id')
                    ->label('Metode Pembayaran')
                    ->relationship('paymentMethod', 'method_name')
                    ->searchable()
                    ->preload(),
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
                TextColumn::make('type')->label('Tipe')->badge(),
                TextColumn::make('category.name')->label('Kategori')->searchable(),
                TextColumn::make('sub_category')->label('Subkategori')->placeholder('-'),
                TextColumn::make('amount')->label('Nominal')->money('IDR'),
                TextColumn::make('paymentMethod.method_name')->label('Metode')->placeholder('-'),
                TextColumn::make('occurred_at')->label('Tanggal')->dateTime(),
                TextColumn::make('description')->label('Deskripsi')->wrap(),
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
        return parent::getEloquentQuery()->whereNull('reference_type');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManualFinanceEntries::route('/'),
            'create' => Pages\CreateManualFinanceEntry::route('/create'),
            'edit' => Pages\EditManualFinanceEntry::route('/{record}/edit'),
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
