<?php

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EntityCreated
{
    use Dispatchable, SerializesModels;

    /**
     * The entity instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public Model $entity;

    /**
     * The user ID who performed the action.
     *
     * @var int|null
     */
    public ?int $userId;

    /**
     * Users to notify about this event.
     *
     * @var array|null
     */
    public ?array $notifyUsers;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $entity
     * @param  int|null  $userId
     * @param  array|null  $notifyUsers
     * @return void
     */
    public function __construct(Model $entity, ?int $userId = null, ?array $notifyUsers = null)
    {
        $this->entity = $entity;
        $this->userId = $userId;
        $this->notifyUsers = $notifyUsers;
    }
}






