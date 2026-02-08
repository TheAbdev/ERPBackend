<?php

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EntityUpdated
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
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $entity
     * @param  int|null  $userId
     * @return void
     */
    public function __construct(Model $entity, ?int $userId = null)
    {
        $this->entity = $entity;
        $this->userId = $userId;
    }
}



























