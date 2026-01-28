<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Core\Models\Tenant;
use App\Modules\CRM\Models\EmailTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailTemplateControllerTest extends TestCase
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

    public function test_can_list_email_templates(): void
    {
        EmailTemplate::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->getJson('/api/crm/email-templates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'subject'],
                ],
            ]);
    }

    public function test_can_create_email_template(): void
    {
        $data = [
            'name' => 'Welcome Email',
            'subject' => 'Welcome to our platform',
            'body' => 'Hello {{name}}, welcome!',
            'type' => 'lead',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/crm/email-templates', $data);

        $response->assertStatus(201);

        $this->assertDatabaseHas('email_templates', [
            'tenant_id' => $this->tenant->id,
            'name' => $data['name'],
            'subject' => $data['subject'],
        ]);
    }

    public function test_can_update_email_template(): void
    {
        $template = EmailTemplate::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $data = [
            'name' => 'Updated Template',
            'is_active' => false,
        ];

        $response = $this->putJson("/api/crm/email-templates/{$template->id}", $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('email_templates', [
            'id' => $template->id,
            'name' => $data['name'],
            'is_active' => false,
        ]);
    }
}



























