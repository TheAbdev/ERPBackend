<?php

namespace App\Modules\CRM\Models;

use App\Core\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTracking extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'email_campaign_id', 'email_message_id', 'recipient_email', 'tracking_token',
        'opened', 'opened_at', 'open_count', 'clicked_links', 'first_clicked_at', 'bounced', 'bounce_reason',
    ];

    protected function casts(): array
    {
        return [
            'clicked_links' => 'array',
            'opened' => 'boolean',
            'bounced' => 'boolean',
            'opened_at' => 'datetime',
            'first_clicked_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    public function emailCampaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class);
    }
}






