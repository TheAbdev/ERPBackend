<?php

namespace App\Modules\CRM\Policies;

use App\Modules\CRM\Models\EmailCampaign;

class EmailCampaignPolicy extends \App\Policies\BasePolicy
{
    protected function getResourceName(): string
    {
        return 'email_campaigns';
    }
}





