<?php

namespace App\Providers;

use App\Events\EntityCreated;
use App\Events\EntityDeleted;
use App\Events\EntityUpdated;
use App\Modules\ERP\Listeners\HandleEntityEvent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        EntityCreated::class => [
            [HandleEntityEvent::class, 'handleCreated'],
        ],
        EntityUpdated::class => [
            [HandleEntityEvent::class, 'handleUpdated'],
        ],
        EntityDeleted::class => [
            [HandleEntityEvent::class, 'handleDeleted'],
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot(): void
    {
        parent::boot();

        // Register event listeners manually to ensure they work
        $listener = app(HandleEntityEvent::class);

        Event::listen(EntityCreated::class, function ($event) use ($listener) {
            $listener->handleCreated($event);
        });

        Event::listen(EntityUpdated::class, function ($event) use ($listener) {
            $listener->handleUpdated($event);
        });

        Event::listen(EntityDeleted::class, function ($event) use ($listener) {
            $listener->handleDeleted($event);
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

