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
use Filament\Schemas\Components\Utilities\Get;
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
                    ->live()
                    ->helperText(fn (Get $get): string => static::getPeriodTypeHelperText(
                        $get('period_type'),
                        $get('start_date'),
                        $get('end_date'),
                    ))
                    ->required(),
                DatePicker::make('start_date')
                    ->label('Mulai')
                    ->live()
                    ->helperText(fn (Get $get): string => static::getStartDateHelperText(
                        $get('period_type'),
                        $get('start_date'),
                        $get('end_date'),
                    ))
                    ->required(),
                DatePicker::make('end_date')
                    ->label('Selesai')
                    ->live()
                    ->helperText(fn (Get $get): string => static::getEndDateHelperText(
                        $get('period_type'),
                        $get('start_date'),
                        $get('end_date'),
                    ))
                    ->required(),
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
    public static function validatePeriodRules(array $data, ?string $statePath = null): void
    {
        $periodType = (string) ($data['period_type'] ?? 'monthly');
        $startDate = Carbon::parse($data['start_date'] ?? now());
        $endDate = Carbon::parse($data['end_date'] ?? now());
        $startField = static::qualifyFieldPath('start_date', $statePath);
        $endField = static::qualifyFieldPath('end_date', $statePath);

        if ($startDate->gt($endDate)) {
            throw ValidationException::withMessages([
                $endField => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            ]);
        }

        if ($periodType === 'daily' && ! $startDate->isSameDay($endDate)) {
            throw ValidationException::withMessages([
                $endField => 'Payroll harian harus menggunakan tanggal mulai dan selesai yang sama.',
            ]);
        }

        if ($periodType === 'monthly') {
            $expectedEnd = $startDate->copy()->addMonthNoOverflow()->subDay();
            $isStartValid = $startDate->day === 26;
            $isEndValid = $endDate->isSameDay($expectedEnd);

            if (! $isStartValid || ! $isEndValid) {
                throw ValidationException::withMessages([
                    $startField => 'Payroll bulanan harus mengikuti cutoff 26-25.',
                    $endField => 'Payroll bulanan harus mengikuti cutoff 26-25.',
                ]);
            }
        }
    }

    public static function getPeriodTypeHelperText(
        mixed $periodType,
        mixed $startDate = null,
        mixed $endDate = null,
    ): string {
        return match ((string) ($periodType ?: 'monthly')) {
            'daily' => 'Payroll harian wajib memakai tanggal mulai dan selesai yang sama.',
            default => 'Payroll bulanan wajib mengikuti cutoff 26-25. ' . static::getMonthlyExampleText($startDate, $endDate),
        };
    }

    public static function getStartDateHelperText(
        mixed $periodType,
        mixed $startDate = null,
        mixed $endDate = null,
    ): string {
        return match ((string) ($periodType ?: 'monthly')) {
            'daily' => 'Untuk payroll harian, isi tanggal mulai sama dengan tanggal selesai.',
            default => 'Untuk payroll bulanan, tanggal mulai harus tanggal 26. ' . static::getMonthlyExampleText($startDate, $endDate),
        };
    }

    public static function getEndDateHelperText(
        mixed $periodType,
        mixed $startDate = null,
        mixed $endDate = null,
    ): string {
        return match ((string) ($periodType ?: 'monthly')) {
            'daily' => 'Untuk payroll harian, tanggal selesai harus sama dengan tanggal mulai.',
            default => 'Untuk payroll bulanan, tanggal selesai harus tanggal 25. ' . static::getMonthlyExampleText($startDate, $endDate),
        };
    }

    protected static function qualifyFieldPath(string $field, ?string $statePath = null): string
    {
        if (blank($statePath)) {
            return $field;
        }

        return "{$statePath}.{$field}";
    }

    protected static function getMonthlyExampleText(mixed $startDate = null, mixed $endDate = null): string
    {
        $exampleStart = null;
        $exampleEnd = null;

        try {
            if (filled($endDate)) {
                $exampleEnd = Carbon::parse($endDate);
                $exampleStart = $exampleEnd->copy()->subMonthNoOverflow()->addDay();
            } elseif (filled($startDate)) {
                $exampleStart = Carbon::parse($startDate)->copy()->day(26);
                $exampleEnd = $exampleStart->copy()->addMonthNoOverflow()->subDay();
            }
        } catch (\Throwable) {
            $exampleStart = null;
            $exampleEnd = null;
        }

        if (! $exampleStart || ! $exampleEnd) {
            return 'Contoh: 26/02/2026 - 25/03/2026.';
        }

        return 'Contoh valid untuk periode ini: ' . $exampleStart->format('d/m/Y') . ' - ' . $exampleEnd->format('d/m/Y') . '.';
    }
}
