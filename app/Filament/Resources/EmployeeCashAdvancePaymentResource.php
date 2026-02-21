<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeCashAdvancePaymentResource\Pages;
use App\Filament\Support\HasSafeDeleteActions;
use App\Models\EmployeeCashAdvance;
use App\Models\EmployeeCashAdvancePayment;
use App\Models\PayrollPeriod;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class EmployeeCashAdvancePaymentResource extends Resource
{
    use HasSafeDeleteActions;

    protected static ?string $model = EmployeeCashAdvancePayment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Payroll';

    protected static ?string $navigationLabel = 'Pembayaran Kasbon';

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
                    ->live()
                    ->searchable()
                    ->preload()
                    ->required(),
                Placeholder::make('remaining_cash_advance')
                    ->label('Sisa Kasbon')
                    ->content(function (Get $get, ?EmployeeCashAdvancePayment $record = null): string {
                        $advanceId = (int) ($get('employee_cash_advance_id') ?? 0);

                        if ($advanceId <= 0) {
                            return '-';
                        }

                        $excludePaymentId = $record?->id;
                        $remaining = static::getRemainingAmount($advanceId, $excludePaymentId);

                        return 'Rp ' . number_format($remaining, 0, ',', '.');
                    }),
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
                static::makeDeleteAction(
                    afterDelete: function (EmployeeCashAdvancePayment $record): void {
                        static::syncCashAdvanceStatusById((int) $record->employee_cash_advance_id);
                    },
                ),
            ])
            ->toolbarActions([
                static::makeDeleteBulkAction(
                    afterDelete: function (EmployeeCashAdvancePayment $record): void {
                        static::syncCashAdvanceStatusById((int) $record->employee_cash_advance_id);
                    },
                ),
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

    public static function validatePaymentAmount(int $cashAdvanceId, float $amount, ?int $excludePaymentId = null): void
    {
        $cashAdvance = EmployeeCashAdvance::query()->find($cashAdvanceId);

        if (! $cashAdvance) {
            throw ValidationException::withMessages([
                'employee_cash_advance_id' => 'Kasbon tidak ditemukan.',
            ]);
        }

        $remaining = $cashAdvance->getRemainingAmount($excludePaymentId);

        if ($remaining <= 0) {
            throw ValidationException::withMessages([
                'employee_cash_advance_id' => 'Kasbon ini sudah lunas.',
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal pembayaran harus lebih besar dari 0.',
            ]);
        }

        if ($amount > $remaining) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal pembayaran melebihi sisa kasbon (Rp ' . number_format($remaining, 0, ',', '.') . ').',
            ]);
        }
    }

    public static function syncCashAdvanceStatusById(?int $cashAdvanceId): void
    {
        if (! $cashAdvanceId) {
            return;
        }

        $cashAdvance = EmployeeCashAdvance::query()->find($cashAdvanceId);

        if (! $cashAdvance) {
            return;
        }

        $cashAdvance->syncSettlementStatus();
    }

    protected static function getRemainingAmount(int $cashAdvanceId, ?int $excludePaymentId = null): float
    {
        $cashAdvance = EmployeeCashAdvance::query()->find($cashAdvanceId);

        if (! $cashAdvance) {
            return 0.0;
        }

        return $cashAdvance->getRemainingAmount($excludePaymentId);
    }
}
