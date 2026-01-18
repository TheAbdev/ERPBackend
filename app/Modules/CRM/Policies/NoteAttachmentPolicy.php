<?php

namespace App\Modules\CRM\Policies;

use App\Policies\BasePolicy;

class NoteAttachmentPolicy extends BasePolicy
{
    protected function getResourceName(): string
    {
        return 'note_attachments';
    }

    protected function getModuleName(): string
    {
        return 'crm';
    }
}

