<?php

namespace App\Modules\ERP\Policies;

class JournalEntryPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'accounting.journals';
    }
}

