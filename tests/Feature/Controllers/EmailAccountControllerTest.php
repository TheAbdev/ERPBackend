<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Core\Models\Tenant;
use App\Modules\CRM\Models\EmailAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailAccountControllerTest extends TestCase
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

    public function test_can_list_email_accounts(): void
    {
        EmailAccount::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->getJson('/api/crm/email-accounts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'type'],
                ],
            ]);
    }

    public function test_can_create_email_account(): void
    {
        $data = [
            'name' => 'Test Email Account',
            'email' => 'test@example.com',
            'type' => 'smtp',
            'credentials' => [
                'host' => 'smtp.example.com',
                'port' => 587,
                'username' => 'test@example.com',
                'password' => 'password123',
            ],
            'is_active' => true,
        ];

        $response = $this->postJson('/api/crm/email-accounts', $data);

        $response->assertStatus(201);

        $this->assertDatabaseHas('email_accounts', [
            'tenant_id' => $this->tenant->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'type' => $data['type'],
        ]);
    }

    public function test_can_update_email_account(): void
    {
        $account = EmailAccount::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $data = [
            'name' => 'Updated Email Account',
            'is_active' => false,
        ];

        $response = $this->putJson("/api/crm/email-accounts/{$account->id}", $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('email_accounts', [
            'id' => $account->id,
            'name' => $data['name'],
            'is_active' => false,
        ]);
    }

    public function test_can_delete_email_account(): void
    {
        $account = EmailAccount::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->deleteJson("/api/crm/email-accounts/{$account->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('email_accounts', [
            'id' => $account->id,
        ]);
    }
}






