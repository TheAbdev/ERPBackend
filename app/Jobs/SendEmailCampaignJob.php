<?php

namespace App\Jobs;

use App\Modules\CRM\Models\EmailCampaign;
use App\Modules\CRM\Models\EmailAccount;
use App\Modules\CRM\Services\EmailTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendEmailCampaignJob extends BaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected EmailCampaign $campaign;
    protected array $recipients;

    /**
     * Create a new job instance.
     */
    public function __construct(EmailCampaign $campaign, array $recipients)
    {
        parent::__construct();
        $this->campaign = $campaign;
        $this->recipients = $recipients;
    }

    /**
     * Execute the job.
     */
    public function handle(EmailTrackingService $trackingService): void
    {
        try {
            // Get active email account for sending
            $emailAccount = EmailAccount::where('tenant_id', $this->campaign->tenant_id)
                ->where('is_active', true)
                ->where('type', 'smtp')
                ->first();

            if (!$emailAccount) {
                Log::error('No active SMTP account found for campaign ' . $this->campaign->id);
                $this->campaign->update(['status' => 'cancelled']);
                return;
            }

            $credentials = $emailAccount->credentials;
            
            // Configure mail settings
            config([
                'mail.mailers.smtp.host' => $credentials['host'] ?? 'smtp.gmail.com',
                'mail.mailers.smtp.port' => $credentials['port'] ?? 587,
                'mail.mailers.smtp.encryption' => $credentials['encryption'] ?? 'tls',
                'mail.mailers.smtp.username' => $emailAccount->email,
                'mail.mailers.smtp.password' => $credentials['password'],
                'mail.from.address' => $emailAccount->email,
                'mail.from.name' => $emailAccount->name,
            ]);

            $sentCount = 0;
            $failedCount = 0;

            foreach ($this->recipients as $recipientEmail) {
                try {
                    // Add tracking to email body
                    $body = $trackingService->addTrackingToBody(
                        $this->campaign->body,
                        $recipientEmail,
                        $this->campaign
                    );

                    // Send email
                    Mail::html($body, function ($message) use ($recipientEmail) {
                        $message->to($recipientEmail)
                            ->subject($this->campaign->subject);
                    });

                    $sentCount++;
                    $this->campaign->increment('sent_count');

                } catch (\Exception $e) {
                    Log::error('Failed to send email to ' . $recipientEmail . ': ' . $e->getMessage());
                    $failedCount++;
                    
                    // Record bounce
                    $trackingService->recordBounce($recipientEmail, $this->campaign, $e->getMessage());
                }
            }

            // Update campaign status
            if ($sentCount > 0) {
                $this->campaign->update([
                    'status' => 'completed',
                    'sent_at' => now(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Email campaign job failed: ' . $e->getMessage());
            $this->campaign->update(['status' => 'cancelled']);
            throw $e;
        }
    }
}




