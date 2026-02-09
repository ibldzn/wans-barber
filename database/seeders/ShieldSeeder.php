<?php

namespace Database\Seeders;

use App\Models\User;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Facades\Filament;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        $panel = Filament::getPanel('kasir');

        if ($panel) {
            Filament::setCurrentPanel($panel);
        }

        $permissions = $this->buildPermissions();

        foreach ($permissions as $permission) {
            Utils::createPermission($permission);
        }

        Utils::giveSuperAdminPermission($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::first();

        if (! $user) {
            return;
        }

        $role = Utils::createRole();

        if (! $user->hasRole($role->name)) {
            $user->assignRole($role);
        }
    }

    /**
     * Build a permission list without relying on FilamentShield::getEntitiesPermissions(),
     * which can be incompatible across versions (pages/widgets key naming).
     *
     * @return array<int, string>
     */
    private function buildPermissions(): array
    {
        $permissions = collect();

        $resourcePermissions = array_keys(FilamentShield::getAllResourcePermissionsWithLabels());
        $permissions = $permissions->merge($resourcePermissions);

        $pagePermissions = $this->extractEntityPermissions(FilamentShield::getPages() ?? []);
        $permissions = $permissions->merge($pagePermissions);

        $widgetPermissions = $this->extractEntityPermissions(FilamentShield::getWidgets() ?? []);
        $permissions = $permissions->merge($widgetPermissions);

        $customPermissions = array_keys(FilamentShield::getCustomPermissions() ?? []);
        $permissions = $permissions->merge($customPermissions);

        return $permissions
            ->filter(fn ($permission) => is_string($permission) && $permission !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<string, array<string, mixed>> $entities
     * @return array<int, string>
     */
    private function extractEntityPermissions(array $entities): array
    {
        return collect($entities)
            ->flatMap(function (array $entity): array {
                $permissions = $entity['permissions'] ?? $entity['permission'] ?? [];

                if (! is_array($permissions)) {
                    return [];
                }

                // If permissions are a list, normalize to keys.
                if (array_is_list($permissions)) {
                    return collect($permissions)
                        ->map(function ($permission) {
                            if (is_array($permission)) {
                                return $permission['key'] ?? null;
                            }

                            return is_string($permission) ? $permission : null;
                        })
                        ->filter()
                        ->values()
                        ->all();
                }

                // Associative array: keys are permission strings.
                return array_keys($permissions);
            })
            ->filter(fn ($permission) => is_string($permission) && $permission !== '')
            ->values()
            ->all();
    }
}
