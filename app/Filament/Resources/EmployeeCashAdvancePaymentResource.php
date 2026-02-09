<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeCashAdvancePaymentResource\Pages;
use App\Models\EmployeeCashAdvance;
use App\Models\EmployeeCashAdvancePayment;
use App\Models\PayrollPeriod;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmployeeCashAdvancePaymentResource extends Resource
{
    protected static ?string $model = EmployeeCashAdvancePayment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Payroll';

    protected static ?string $navigationLabel = 'Cash Advance Payments';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Pembayaran Kasbon')->schema([
                Select::make('employee_cash_advance_id')
                    ->label('Kasbon')
                    ->relationship(
                        'cashAdvance',
                        'id',
                        modifyQueryUsing: fn($query) => $query->where('status', 'open')
                    )
                    ->getOptionLabelFromRecordUsing(function (EmployeeCashAdvance $record): string {
                        $employee = $record->employee?->emp_name ?? 'Pegawai';
                        $amount = number_format((float) $record->amount, 0, ',', '.');
                        return "{$employee} - Rp {$amount}";
                    })
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('payroll_period_id')
                    ->label('Periode Payroll')
                    ->relationship('payrollPeriod', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn() => PayrollPeriod::where('status', 'open')->latest('start_date')->value('id')),
                TextInput::make('amount')
                    ->label('Nominal')
                    ->numeric()
                    ->required(),
                DatePicker::make('paid_at')
                    ->label('Tanggal')
                    ->default(now())
                    ->required(),
                TextInput::make('description')
                    ->label('Deskripsi'),
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
                TextColumn::make('cashAdvance.employee.emp_name')->label('Pegawai')->searchable(),
                TextColumn::make('cashAdvance.amount')->label('Kasbon')->money('IDR'),
                TextColumn::make('amount')->label('Bayar')->money('IDR'),
                TextColumn::make('payrollPeriod.name')->label('Periode'),
                TextColumn::make('paid_at')->label('Tanggal')->date(),
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
            'index' => Pages\ListEmployeeCashAdvancePayments::route('/'),
            'create' => Pages\CreateEmployeeCashAdvancePayment::route('/create'),
            'edit' => Pages\EditEmployeeCashAdvancePayment::route('/{record}/edit'),
        ];
    }
}
