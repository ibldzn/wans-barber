<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserResourceSyncUserAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_assigns_the_panel_role_using_the_default_web_guard(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        UserResource::syncUserAccess($user);

        $role = Role::query()
            ->where('name', 'admin')
            ->where('guard_name', 'web')
            ->first();

        $this->assertNotNull($role);
        $this->assertTrue($user->fresh()->hasRole('admin'));
        $this->assertSame('admin', $user->fresh()->role);
    }

    public function test_it_links_the_selected_employee_to_the_user(): void
    {
        $employee = $this->createEmployee('Barber One');

        $user = User::factory()->create([
            'role' => 'admin',
            'employee_id' => $employee->id,
        ]);

        UserResource::syncUserAccess($user);

        $this->assertSame($user->id, $employee->fresh()->user_id);
    }

    public function test_it_clears_the_previous_employee_link_when_reassigned(): void
    {
        $previousEmployee = $this->createEmployee('Barber One');
        $newEmployee = $this->createEmployee('Barber Two');

        $user = User::factory()->create([
            'role' => 'admin',
            'employee_id' => $newEmployee->id,
        ]);

        $previousEmployee->update(['user_id' => $user->id]);

        UserResource::syncUserAccess($user, $previousEmployee->id);

        $this->assertNull($previousEmployee->fresh()->user_id);
        $this->assertSame($user->id, $newEmployee->fresh()->user_id);
    }

    private function createEmployee(string $name): Employee
    {
        return Employee::query()->create([
            'emp_name' => $name,
            'emp_phone' => '08123456789',
            'role' => 'barber',
            'bank_account' => null,
            'monthly_salary' => 0,
            'meal_allowance_per_day' => 0,
            'is_active' => true,
        ]);
    }
}
