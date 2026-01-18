<?php

namespace App\Modules\ERP\Policies;

class WebhookPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'webhooks';
    }
}

