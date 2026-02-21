<?php

namespace App\Filament\Pages\Payroll;

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DailyAttendancePage extends Page
{
    use InteractsWithForms;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|\UnitEnum|null $navigationGroup = 'Payroll';

    protected static ?string $navigationLabel = 'Absensi Harian';

    protected static ?string $title = 'Absensi Harian';

    protected string $view = 'filament.pages.payroll.daily-attendance-page';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'date' => now()->toDateString(),
            'rows' => [],
        ]);

        $this->loadRowsBySelectedDate();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Input Absensi')->schema([
                    DatePicker::make('date')
                        ->label('Tanggal')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (): void {
                            $this->loadRowsBySelectedDate();
                        }),
                    Repeater::make('rows')
                        ->label('Daftar Pegawai')
                        ->schema([
                            Hidden::make('employee_id'),
                            TextInput::make('employee_name')
                                ->label('Pegawai')
                                ->disabled()
                                ->dehydrated(false),
                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'present' => 'Hadir',
                                    'absent' => 'Tidak Hadir',
                                ])
                                ->required(),
                            TextInput::make('notes')
                                ->label('Catatan'),
                        ])
                        ->columns(3)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false),
                ])
                    ->columnSpanFull()
                    ->inlineLabel()
                    ->columns(1),
            ])
            ->statePath('data');
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('setAllPresent')
                ->label('Set All Hadir')
                ->color('gray')
                ->action(function (): void {
                    $this->setAllStatus('present');
                }),
            Action::make('setAllAbsent')
                ->label('Set All Tidak Hadir')
                ->color('gray')
                ->action(function (): void {
                    $this->setAllStatus('absent');
                }),
            Action::make('save')
                ->label('Simpan')
                ->color('primary')
                ->action(function (): void {
                    $this->saveAttendance();
                }),
        ];
    }

    protected function loadRowsBySelectedDate(): void
    {
        $selectedDate = $this->data['date'] ?? null;

        if (! $selectedDate) {
            return;
        }

        $date = Carbon::parse($selectedDate)->toDateString();

        $employees = Employee::query()
            ->where('is_active', true)
            ->orderBy('emp_name')
            ->get(['id', 'emp_name']);

        $attendances = EmployeeAttendance::query()
            ->whereDate('date', $date)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->keyBy('employee_id');

        $rows = $employees
            ->map(function (Employee $employee) use ($attendances): array {
                $attendance = $attendances->get($employee->id);

                return [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->emp_name,
                    'status' => $attendance?->status ?? 'present',
                    'notes' => $attendance?->notes,
                ];
            })
            ->values()
            ->all();

        $this->form->fill([
            'date' => $date,
            'rows' => $rows,
        ]);
    }

    protected function setAllStatus(string $status): void
    {
        $rows = $this->data['rows'] ?? [];

        foreach ($rows as &$row) {
            $row['status'] = $status;
        }

        unset($row);

        $this->data['rows'] = $rows;
        $this->form->fill($this->data);
    }

    public function saveAttendance(): void
    {
        $state = $this->form->getState();
        $date = Carbon::parse($state['date'] ?? now())->toDateString();
        $rows = $state['rows'] ?? [];

        if ($rows === []) {
            Notification::make()
                ->title('Tidak ada pegawai aktif')
                ->warning()
                ->send();

            return;
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($rows, $date, &$created, &$updated): void {
            $activeEmployeeIds = Employee::query()
                ->where('is_active', true)
                ->pluck('id')
                ->all();

            foreach ($rows as $row) {
                $employeeId = (int) ($row['employee_id'] ?? 0);

                if (! in_array($employeeId, $activeEmployeeIds, true)) {
                    continue;
                }

                $attendance = EmployeeAttendance::updateOrCreate(
                    [
                        'employee_id' => $employeeId,
                        'date' => $date,
                    ],
                    [
                        'status' => ($row['status'] ?? 'present') === 'absent' ? 'absent' : 'present',
                        'notes' => $row['notes'] ?: null,
                    ],
                );

                if ($attendance->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }
        });

        Notification::make()
            ->title('Absensi tersimpan')
            ->body("Create: {$created} data, Update: {$updated} data.")
            ->success()
            ->send();

        $this->loadRowsBySelectedDate();
    }
}
