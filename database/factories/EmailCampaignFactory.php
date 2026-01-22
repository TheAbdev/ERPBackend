<?php

namespace Database\Factories;

use App\Core\Models\Tenant;
use App\Models\User;
use App\Modules\CRM\Models\EmailCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailCampaignFactory extends Factory
{
    protected $model = EmailCampaign::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'email_template_id' => null,
            'name' => fake()->words(3, true) . ' Campaign',
            'subject' => fake()->sentence(),
            'body' => fake()->paragraphs(3, true),
            'status' => fake()->randomElement(['draft', 'scheduled', 'sending', 'completed', 'cancelled']),
            'scheduled_at' => null,
            'sent_at' => null,
            'recipients' => [fake()->email(), fake()->email()],
            'recipient_type' => fake()->randomElement(['lead', 'contact', 'all', null]),
            'total_recipients' => 2,
            'sent_count' => 0,
            'opened_count' => 0,
            'clicked_count' => 0,
            'bounced_count' => 0,
            'created_by' => User::factory(),
        ];
    }
}
















