<?php

namespace App\Events;

use App\Modules\CRM\Models\Deal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DealStatusChanged
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Deal $deal,
        public string $action,
        public ?string $oldStatus = null
    ) {
        //
    }
}
