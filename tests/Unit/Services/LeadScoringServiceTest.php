<?php

namespace Tests\Unit\Services;

use App\Modules\CRM\Services\LeadScoringService;
use App\Modules\CRM\Models\Lead;
use App\Core\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected LeadScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->service = new LeadScoringService();
    }

    /** @test */
    public function it_calculates_lead_score_based_on_criteria(): void
    {
        $lead = Lead::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'source' => 'website',
        ]);

        $score = $this->service->calculateScore($lead);

        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /** @test */
    public function it_handles_leads_without_email(): void
    {
        $lead = Lead::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => null,
            'phone' => '+1234567890',
        ]);

        $score = $this->service->calculateScore($lead);

        $this->assertIsInt($score);
    }
}













