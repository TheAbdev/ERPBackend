<?php

namespace Tests\Feature\Controllers;

use App\Core\Models\Tenant;
use App\Models\User;
use App\Modules\HR\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HrPayrollControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_create_a_payroll(): void
    {
        $employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'status' => 'active',
            'basic_salary' => 1000,
        ]);

        $payload = [
            'employee_id' => $employee->id,
            'period_start' => '2025-01-01',
            'period_end' => '2025-01-31',
            'base_salary' => 1000,
            'allowances' => 100,
            'deductions' => 50,
            'status' => 'draft',
        ];

        $response = $this->postJson('/api/hr/payrolls', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'employee_id', 'net_salary']]);

        $this->assertDatabaseHas('hr_payrolls', [
            'employee_id' => $employee->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }
}

