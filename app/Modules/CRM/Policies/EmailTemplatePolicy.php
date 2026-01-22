<?php

namespace App\Modules\CRM\Policies;

use App\Modules\CRM\Models\EmailTemplate;

class EmailTemplatePolicy extends \App\Policies\BasePolicy
{
    protected function getResourceName(): string
    {
        return 'email_templates';
    }
}
















