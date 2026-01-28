<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Core\Models\Tenant;
use App\Modules\CRM\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_list_leads(): void
    {
        Lead::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson('/api/crm/leads');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'status'],
                ],
            ]);
    }

    /** @test */
    public function it_can_create_a_lead(): void
    {
        $leadData = [
            'name' => 'Test Lead',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'status' => 'new',
        ];

        $response = $this->postJson('/api/crm/leads', $leadData);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'name', 'email']]);

        $this->assertDatabaseHas('leads', [
            'name' => 'Test Lead',
            'email' => 'test@example.com',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /** @test */
    public function it_can_show_a_lead(): void
    {
        $lead = Lead::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson("/api/crm/leads/{$lead->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'name', 'email']]);
    }

    /** @test */
    public function it_can_update_a_lead(): void
    {
        $lead = Lead::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->putJson("/api/crm/leads/{$lead->id}", [
            'name' => 'Updated Lead',
            'email' => $lead->email,
            'status' => 'contacted',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'name' => 'Updated Lead',
            'status' => 'contacted',
        ]);
    }

    /** @test */
    public function it_can_delete_a_lead(): void
    {
        $lead = Lead::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->deleteJson("/api/crm/leads/{$lead->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('leads', ['id' => $lead->id]);
    }
}

























