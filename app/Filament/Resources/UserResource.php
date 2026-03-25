<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Support\HasSafeDeleteActions;
use App\Models\Employee;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    use HasSafeDeleteActions;

    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Users';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Akun User')->schema([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
                Select::make('role')
                    ->label('Role Panel')
                    ->options(static::getPanelRoleOptions())
                    ->default('admin')
                    ->required(),
                Select::make('employee_id')
                    ->label('Pegawai Terkait')
                    ->options(fn (?User $record = null): array => static::getAssignableEmployeeOptions($record))
                    ->searchable()
                    ->preload()
                    ->helperText('Opsional. Satu pegawai hanya boleh terhubung ke satu user.')
                    ->rules([
                        fn (?User $record = null): \Closure => function (string $attribute, $value, \Closure $fail) use ($record): void {
                            if (blank($value)) {
                                return;
                            }

                            $employee = Employee::query()->find($value);

                            if (! $employee) {
                                $fail('Pegawai tidak ditemukan.');

                                return;
                            }

                            if ($employee->user_id && $employee->user_id !== $record?->id) {
                                $fail('Pegawai ini sudah terhubung ke user lain.');

                                return;
                            }

                            $linkedUserExists = User::query()
                                ->where('employee_id', $employee->id)
                                ->when($record, fn (Builder $query) => $query->whereKeyNot($record->id))
                                ->exists();

                            if ($linkedUserExists) {
                                $fail('Pegawai ini sudah dipakai oleh user lain.');
                            }
                        },
                    ]),
            ])
                ->columnSpanFull()
                ->inlineLabel()
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['employee', 'roles']))
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('panel_role')
                    ->label('Role')
                    ->state(fn (User $record): string => $record->roles->first()?->name ?? $record->role ?? '-')
                    ->badge(),
                TextColumn::make('employee.emp_name')->label('Pegawai')->placeholder('-'),
                TextColumn::make('created_at')->label('Dibuat')->dateTime()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Diubah')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                static::makeDeleteAction(
                    afterDelete: fn (User $record) => static::clearEmployeeLink($record),
                ),
            ])
            ->toolbarActions([
                static::makeDeleteBulkAction(
                    afterDelete: fn (User $record) => static::clearEmployeeLink($record),
                ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return static::canAccessByRole();
    }

    public static function canCreate(): bool
    {
        return static::canAccessByRole();
    }

    public static function canEdit($record): bool
    {
        return static::canAccessByRole();
    }

    public static function canDelete($record): bool
    {
        return static::canAccessByRole();
    }

    public static function syncUserAccess(User $user, ?int $previousEmployeeId = null): void
    {
        $roleName = array_key_exists($user->role, static::getPanelRoleOptions()) ? $user->role : 'admin';

        $role = Role::findOrCreate($roleName, $user->getDefaultGuardName());
        $user->syncRoles([$role]);
        $user->forceFill(['role' => $roleName])->saveQuietly();

        $currentEmployeeId = $user->employee_id;

        Employee::query()
            ->where('user_id', $user->id)
            ->when($currentEmployeeId, fn (Builder $query) => $query->whereKeyNot($currentEmployeeId))
            ->update(['user_id' => null]);

        if ($previousEmployeeId && $previousEmployeeId !== $currentEmployeeId) {
            Employee::query()
                ->whereKey($previousEmployeeId)
                ->where('user_id', $user->id)
                ->update(['user_id' => null]);
        }

        if ($currentEmployeeId) {
            Employee::query()
                ->whereKey($currentEmployeeId)
                ->update(['user_id' => $user->id]);
        }
    }

    public static function clearEmployeeLink(User $user): void
    {
        Employee::query()
            ->where('user_id', $user->id)
            ->update(['user_id' => null]);
    }

    public static function getPanelRoleOptions(): array
    {
        return [
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'kasir' => 'Kasir',
        ];
    }

    protected static function getAssignableEmployeeOptions(?User $record = null): array
    {
        return Employee::query()
            ->where(function (Builder $query) use ($record): void {
                $query->whereNull('user_id');

                if ($record?->employee_id) {
                    $query->orWhereKey($record->employee_id);
                }
            })
            ->orderBy('emp_name')
            ->pluck('emp_name', 'id')
            ->all();
    }

    protected static function canAccessByRole(): bool
    {
        $user = auth()->user();

        return (bool) ($user && $user->isAdmin());
    }
}
