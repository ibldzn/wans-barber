<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayslipResource\Pages;
use App\Models\Payslip;
use App\Services\PayrollService;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PayslipResource extends Resource
{
    protected static ?string $model = Payslip::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Payroll';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.emp_name')->label('Pegawai')->searchable(),
                TextColumn::make('payrollPeriod.name')->label('Periode'),
                TextColumn::make('net_pay')->label('Total Dibayar')->money('IDR'),
                TextColumn::make('paid_at')->label('Paid At')->dateTime(),
            ])
            ->recordActions([
                Action::make('print')
                    ->label('PDF')
                    ->url(fn(Payslip $record) => route('payslips.pdf', $record))
                    ->openUrlInNewTab(),
                Action::make('markPaid')
                    ->label('Mark Paid')
                    ->requiresConfirmation()
                    ->visible(fn(Payslip $record) => $record->paid_at === null)
                    ->action(function (Payslip $record): void {
                        app(PayrollService::class)->markPayslipPaid($record, auth()->user());
                        Notification::make()
                            ->title('Payslip ditandai paid')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayslips::route('/'),
        ];
    }
}
