<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Pegawai';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Informasi Pegawai')->schema([
                TextInput::make('emp_name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('emp_phone')
                    ->label('No HP')
                    ->tel()
                    ->maxLength(255),
                Select::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'kasir' => 'Kasir',
                        'barber' => 'Barber',
                        'reflexology' => 'Reflexology',
                        'ob' => 'OB',
                    ])
                    ->required(),
                TextInput::make('bank_account')
                    ->label('No Rekening')
                    ->maxLength(255),
                TextInput::make('daily_wage')
                    ->label('Gaji Harian')
                    ->numeric()
                    ->default(0),
                TextInput::make('monthly_salary')
                    ->label('Gaji Bulanan')
                    ->numeric()
                    ->default(0),
                TextInput::make('meal_allowance_per_day')
                    ->label('Uang Makan Harian')
                    ->numeric()
                    ->default(0),
                TextInput::make('commission_rate_override_regular')
                    ->label('Override Komisi Reguler')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(1),
                TextInput::make('commission_rate_override_callout')
                    ->label('Override Komisi Panggilan')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(1),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                Select::make('user_id')
                    ->label('Akun User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
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
                TextColumn::make('emp_name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('role')->label('Role')->badge(),
                TextColumn::make('emp_phone')->label('No HP'),
                TextColumn::make('daily_wage')->label('Gaji Harian')->money('IDR'),
                TextColumn::make('monthly_salary')->label('Gaji Bulanan')->money('IDR'),
                ToggleColumn::make('is_active')->label('Aktif'),
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
