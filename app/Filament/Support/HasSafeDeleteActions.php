<?php

namespace App\Filament\Support;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Throwable;

trait HasSafeDeleteActions
{
    protected static function makeDeleteAction(
        ?Closure $guard = null,
        ?string $guardFailureMessage = null,
        bool $suggestArchive = false,
        bool $hideWhenGuardFails = false,
        ?Closure $afterDelete = null,
    ): Action {
        return Action::make('delete')
            ->label('Delete')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(function (Model $record) use ($guard, $hideWhenGuardFails): bool {
                if (! static::canDelete($record)) {
                    return false;
                }

                if ($hideWhenGuardFails && $guard && ! (bool) $guard($record)) {
                    return false;
                }

                return true;
            })
            ->action(function (Model $record) use ($guard, $guardFailureMessage, $suggestArchive, $afterDelete): void {
                if ($guard && ! (bool) $guard($record)) {
                    Notification::make()
                        ->title('Data tidak bisa dihapus')
                        ->body($guardFailureMessage ?? 'Data tidak memenuhi syarat untuk dihapus.')
                        ->danger()
                        ->send();

                    return;
                }

                try {
                    $record->delete();

                    if ($afterDelete) {
                        $afterDelete($record);
                    }

                    Notification::make()
                        ->title('Data berhasil dihapus')
                        ->success()
                        ->send();
                } catch (QueryException) {
                    Notification::make()
                        ->title('Data tidak bisa dihapus')
                        ->body(static::getDeleteDependencyMessage($suggestArchive))
                        ->danger()
                        ->send();
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->title('Gagal menghapus data')
                        ->body('Terjadi kesalahan saat menghapus data.')
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function makeDeleteBulkAction(
        ?Closure $guard = null,
        ?string $guardFailureMessage = null,
        bool $suggestArchive = false,
        ?Closure $afterDelete = null,
    ): BulkAction {
        return BulkAction::make('deleteSelected')
            ->label('Delete Selected')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->authorizeIndividualRecords('delete')
            ->deselectRecordsAfterCompletion()
            ->action(function (EloquentCollection $records) use ($guard, $guardFailureMessage, $suggestArchive, $afterDelete): void {
                $deleted = 0;
                $guardFailures = 0;
                $dependencyFailures = 0;
                $unexpectedFailures = 0;

                foreach ($records as $record) {
                    if (! $record instanceof Model) {
                        continue;
                    }

                    if ($guard && ! (bool) $guard($record)) {
                        $guardFailures++;
                        continue;
                    }

                    try {
                        $record->delete();

                        if ($afterDelete) {
                            $afterDelete($record);
                        }

                        $deleted++;
                    } catch (QueryException) {
                        $dependencyFailures++;
                    } catch (Throwable $exception) {
                        report($exception);
                        $unexpectedFailures++;
                    }
                }

                if ($deleted > 0) {
                    Notification::make()
                        ->title('Delete selesai')
                        ->body("Berhasil menghapus {$deleted} data.")
                        ->success()
                        ->send();
                }

                if (($guardFailures + $dependencyFailures + $unexpectedFailures) <= 0) {
                    return;
                }

                $messages = [];

                if ($guardFailures > 0) {
                    $messages[] = ($guardFailureMessage ?? 'Sebagian data tidak memenuhi syarat delete.') . " ({$guardFailures} data)";
                }

                if ($dependencyFailures > 0) {
                    $messages[] = static::getDeleteDependencyMessage($suggestArchive) . " ({$dependencyFailures} data)";
                }

                if ($unexpectedFailures > 0) {
                    $messages[] = "Gagal menghapus {$unexpectedFailures} data karena error internal.";
                }

                Notification::make()
                    ->title('Sebagian data gagal dihapus')
                    ->body(implode("\n", $messages))
                    ->danger()
                    ->send();
            });
    }

    protected static function getDeleteDependencyMessage(bool $suggestArchive = false): string
    {
        if ($suggestArchive) {
            return 'Data sudah dipakai transaksi. Ubah status Aktif menjadi nonaktif jika ingin disembunyikan.';
        }

        return 'Data sudah dipakai transaksi sehingga tidak dapat dihapus.';
    }
}
