<?php

namespace App\Filament\Widgets;

use App\Models\EmployeeCashAdvance;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class OutstandingCashAdvanceWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->isAdmin() || $user->isKasir();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Kasbon Outstanding')
            ->description('Daftar pinjaman pegawai yang belum lunas.')
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('employee.emp_name')
                    ->label('Pegawai')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Total Kasbon')
                    ->money('IDR'),
                TextColumn::make('total_paid')
                    ->label('Sudah Bayar')
                    ->state(fn (EmployeeCashAdvance $record): float => (float) ($record->total_paid ?? 0))
                    ->money('IDR'),
                TextColumn::make('remaining_amount')
                    ->label('Sisa')
                    ->state(fn (EmployeeCashAdvance $record): float => max(0, (float) $record->amount - (float) ($record->total_paid ?? 0)))
                    ->money('IDR')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('date')
                    ->label('Tanggal Kasbon')
                    ->date(),
            ])
            ->paginated([5, 10, 25]);
    }

    protected function getTableQuery(): Builder
    {
        $paidExpression = <<<SQL
            (
                SELECT COALESCE(SUM(employee_cash_advance_payments.amount), 0)
                FROM employee_cash_advance_payments
                WHERE employee_cash_advance_payments.employee_cash_advance_id = employee_cash_advances.id
            )
        SQL;

        return EmployeeCashAdvance::query()
            ->with('employee')
            ->select('employee_cash_advances.*')
            ->selectRaw("{$paidExpression} as total_paid")
            ->whereRaw("(employee_cash_advances.amount - {$paidExpression}) > 0")
            ->orderByDesc('date');
    }
}
