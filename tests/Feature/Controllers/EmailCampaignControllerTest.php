<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Core\Models\Tenant;
use App\Modules\CRM\Models\EmailCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailCampaignControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_can_list_email_campaigns(): void
    {
        EmailCampaign::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/crm/email-campaigns');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'status'],
                ],
            ]);
    }

    public function test_can_create_email_campaign(): void
    {
        $data = [
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'body' => 'Test Body',
            'recipients' => ['test1@example.com', 'test2@example.com'],
            'recipient_type' => 'contact',
        ];

        $response = $this->postJson('/api/crm/email-campaigns', $data);

        $response->assertStatus(201);

        $this->assertDatabaseHas('email_campaigns', [
            'tenant_id' => $this->tenant->id,
            'name' => $data['name'],
            'status' => 'draft',
            'total_recipients' => 2,
        ]);
    }

    public function test_can_send_email_campaign(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->postJson("/api/crm/email-campaigns/{$campaign->id}/send");

        $response->assertStatus(200);

        $this->assertDatabaseHas('email_campaigns', [
            'id' => $campaign->id,
            'status' => 'sending',
        ]);
    }
}



























