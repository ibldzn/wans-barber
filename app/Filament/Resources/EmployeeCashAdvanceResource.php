<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeCashAdvanceResource\Pages;
use App\Models\EmployeeCashAdvance;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmployeeCashAdvanceResource extends Resource
{
    protected static ?string $model = EmployeeCashAdvance::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string|\UnitEnum|null $navigationGroup = 'Payroll';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Cash Advance')->schema([
                Select::make('employee_id')
                    ->label('Pegawai')
                    ->relationship('employee', 'emp_name')
                    ->searchable()
                    ->required(),
                TextInput::make('amount')
                    ->label('Nominal')
                    ->numeric()
                    ->required(),
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
            ->columns([
                TextColumn::make('employee.emp_name')->label('Pegawai')->searchable(),
                TextColumn::make('amount')->label('Nominal')->money('IDR'),
                TextColumn::make('date')->label('Tanggal')->date(),
                TextColumn::make('status')->label('Status')->badge(),
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
            'index' => Pages\ListEmployeeCashAdvances::route('/'),
            'create' => Pages\CreateEmployeeCashAdvance::route('/create'),
            'edit' => Pages\EditEmployeeCashAdvance::route('/{record}/edit'),
        ];
    }
}
