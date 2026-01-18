<?php

namespace App\Jobs;

use App\Modules\ERP\Models\WebhookDelivery;
use App\Modules\ERP\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeliverWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public WebhookDelivery $delivery
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(WebhookService $webhookService): void
    {
        $webhookService->deliver($this->delivery);
    }
}

