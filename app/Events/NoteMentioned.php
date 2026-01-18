<?php

namespace App\Events;

use App\Models\User;
use App\Modules\CRM\Models\Note;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NoteMentioned
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Note $note,
        public User $mentionedUser,
        public User $mentionedBy
    ) {
        //
    }
}
