<?php

namespace Tests\Feature;

use App\Filament\Pages\DocumentationPage;
use App\Filament\Resources\DocumentationItemResource;
use App\Filament\Resources\ManualFinanceEntryResource;
use App\Filament\Resources\StockAdjustmentResource;
use App\Filament\Resources\UserResource;
use App\Models\User;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KasirPermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $panel = Filament::getPanel('kasir');

        if ($panel) {
            Filament::setCurrentPanel($panel);
        }
    }

    public function test_shield_seeder_syncs_kasir_permissions_except_user_and_role(): void
    {
        $this->seed(ShieldSeeder::class);

        $kasirRole = Role::findByName('kasir', 'web');

        $this->assertTrue($kasirRole->hasPermissionTo('ViewAny:Employee'));
        $this->assertTrue($kasirRole->hasPermissionTo('View:DocumentationPage'));
        $this->assertTrue($kasirRole->hasPermissionTo('View:FullWidthAccountWidget'));
        $this->assertFalse($kasirRole->hasPermissionTo('ViewAny:User'));
        $this->assertFalse($kasirRole->hasPermissionTo('ViewAny:Role'));

        $firstPermissionCount = $kasirRole->permissions()->count();

        $kasirRole->givePermissionTo('ViewAny:User');
        $kasirRole->givePermissionTo('ViewAny:Role');

        $this->seed(ShieldSeeder::class);

        $kasirRole = Role::findByName('kasir', 'web');

        $this->assertFalse($kasirRole->hasPermissionTo('ViewAny:User'));
        $this->assertFalse($kasirRole->hasPermissionTo('ViewAny:Role'));
        $this->assertSame($firstPermissionCount, $kasirRole->permissions()->count());
    }

    public function test_kasir_can_access_seeded_resources_but_not_roles_or_users(): void
    {
        $user = $this->createKasirUser();

        $this->actingAs($user);

        $this->assertTrue(StockAdjustmentResource::canViewAny());
        $this->assertTrue(ManualFinanceEntryResource::canViewAny());
        $this->assertTrue(DocumentationItemResource::canViewAny());
        $this->assertFalse(UserResource::canViewAny());
        $this->assertFalse(RoleResource::canViewAny());
    }

    public function test_documentation_page_allows_kasir_to_reveal_secrets(): void
    {
        $user = $this->createKasirUser();

        $this->actingAs($user);

        $page = new DocumentationPage();
        $viewData = $page->getViewData();

        $this->assertTrue(DocumentationPage::canAccess());
        $this->assertTrue($viewData['canRevealSecrets']);
    }

    private function createKasirUser(): User
    {
        $this->seed(ShieldSeeder::class);

        $user = User::factory()->create([
            'role' => 'kasir',
        ]);

        $user->assignRole('kasir');

        return $user->fresh();
    }
}
