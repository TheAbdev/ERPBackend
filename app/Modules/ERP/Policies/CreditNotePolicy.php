<?php

namespace App\Modules\ERP\Policies;

class CreditNotePolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'credit_notes';
    }
}

