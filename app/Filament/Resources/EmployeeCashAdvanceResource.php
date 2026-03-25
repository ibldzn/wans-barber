<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeCashAdvanceResource\Pages;
use App\Filament\Support\HasSafeDeleteActions;
use App\Models\EmployeeCashAdvance;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmployeeCashAdvanceResource extends Resource
{
    use HasSafeDeleteActions;

    protected static ?string $model = EmployeeCashAdvance::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string|\UnitEnum|null $navigationGroup = 'Payroll';

    protected static ?string $navigationLabel = 'Kasbon Pegawai';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Cash Advance')->schema([
                Select::make('employee_id')
                    ->label('Pegawai')
                    ->relationship('employee', 'emp_name')
                    ->preload()
                    ->searchable()
                    ->required(),
                TextInput::make('amount')
                    ->label('Nominal')
                    ->numeric()
                    ->required(),
                TextInput::make('installment_amount')
                    ->label('Cicilan Default')
                    ->numeric()
                    ->helperText('Opsional untuk referensi internal. Payroll tidak lagi memotong kasbon otomatis.'),
                DatePicker::make('date')
                    ->label('Tanggal')
                    ->required(),
                TextInput::make('description')
                    ->label('Deskripsi'),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Open',
                        'settled' => 'Settled',
                    ])
                    ->default('open'),
            ])
                ->columnSpanFull()
                ->inlineLabel()
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                return $query
                    ->with(['employee', 'payments'])
                    ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
                    ->orderByDesc('date');
            })
            ->columns([
                TextColumn::make('employee.emp_name')->label('Pegawai')->searchable(),
                TextColumn::make('amount')->label('Nominal')->money('IDR'),
                TextColumn::make('total_paid')
                    ->label('Sudah Dibayar')
                    ->state(fn(EmployeeCashAdvance $record): float => $record->getTotalPaid())
                    ->money('IDR'),
                TextColumn::make('remaining_amount')
                    ->label('Sisa Kasbon')
                    ->state(fn(EmployeeCashAdvance $record): float => $record->getRemainingAmount())
                    ->money('IDR'),
                TextColumn::make('installment_amount')->label('Cicilan')->money('IDR')->placeholder('-'),
                TextColumn::make('date')->label('Tanggal')->date(),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->filters([
                SelectFilter::make('employee_id')
                    ->label('Pegawai')
                    ->relationship('employee', 'emp_name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Open',
                        'settled' => 'Settled',
                    ]),
            ])
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployeeCashAdvances::route('/'),
            'create' => Pages\CreateEmployeeCashAdvance::route('/create'),
            'edit' => Pages\EditEmployeeCashAdvance::route('/{record}/edit'),
        ];
    }
}
