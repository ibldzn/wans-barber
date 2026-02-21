<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayrollPeriodResource\Pages;
use App\Models\PayrollPeriod;
use App\Services\PayrollService;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class PayrollPeriodResource extends Resource
{
    protected static ?string $model = PayrollPeriod::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static string|\UnitEnum|null $navigationGroup = 'Payroll';

    protected static ?string $navigationLabel = 'Periode Payroll';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Periode Payroll')->schema([
                TextInput::make('name')->label('Nama')->required(),
                Select::make('period_type')
                    ->label('Tipe Payroll')
                    ->options([
                        'daily' => 'Harian',
                        'monthly' => 'Bulanan',
                    ])
                    ->default('monthly')
                    ->required(),
                DatePicker::make('start_date')->label('Mulai')->required(),
                DatePicker::make('end_date')->label('Selesai')->required(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Open',
                        'closed' => 'Closed',
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
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('period_type')->label('Tipe')->badge(),
                TextColumn::make('start_date')->label('Mulai')->date(),
                TextColumn::make('end_date')->label('Selesai')->date(),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('generatePayslips')
                    ->label('Generate Payslips')
                    ->requiresConfirmation()
                    ->action(function (PayrollPeriod $record): void {
                        $count = app(PayrollService::class)->generatePayslips($record);
                        Notification::make()
                            ->title('Payslip dibuat')
                            ->body("Total: {$count} pegawai")
                            ->success()
                            ->send();
                    }),
                \Filament\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrollPeriods::route('/'),
            'create' => Pages\CreatePayrollPeriod::route('/create'),
            'edit' => Pages\EditPayrollPeriod::route('/{record}/edit'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function validatePeriodRules(array $data): void
    {
        $periodType = (string) ($data['period_type'] ?? 'monthly');
        $startDate = Carbon::parse($data['start_date'] ?? now());
        $endDate = Carbon::parse($data['end_date'] ?? now());

        if ($startDate->gt($endDate)) {
            throw ValidationException::withMessages([
                'end_date' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            ]);
        }

        if ($periodType === 'daily' && ! $startDate->isSameDay($endDate)) {
            throw ValidationException::withMessages([
                'end_date' => 'Payroll harian harus menggunakan tanggal mulai dan selesai yang sama.',
            ]);
        }

        if ($periodType === 'monthly') {
            $expectedEnd = $startDate->copy()->addMonthNoOverflow()->subDay();
            $isStartValid = $startDate->day === 26;
            $isEndValid = $endDate->isSameDay($expectedEnd);

            if (! $isStartValid || ! $isEndValid) {
                throw ValidationException::withMessages([
                    'start_date' => 'Payroll bulanan harus mengikuti cutoff 26-25.',
                    'end_date' => 'Payroll bulanan harus mengikuti cutoff 26-25.',
                ]);
            }
        }
    }
}
