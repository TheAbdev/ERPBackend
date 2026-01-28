<?php

namespace App\Modules\CRM\Services;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\EmailCampaign;
use App\Modules\CRM\Models\EmailTracking;
use Illuminate\Support\Str;

class EmailTrackingService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Add tracking to email body.
     */
    public function addTrackingToBody(string $body, string $recipientEmail, ?EmailCampaign $campaign = null): string
    {
        // Generate tracking token
        $token = $this->generateTrackingToken($recipientEmail, $campaign);

        // Add tracking pixel
        $trackingPixel = $this->getTrackingPixelUrl($token);
        $body .= '<img src="' . $trackingPixel . '" width="1" height="1" style="display:none;" />';

        // Replace all links with tracking links
        $body = $this->replaceLinksWithTracking($body, $token);

        return $body;
    }

    /**
     * Generate tracking token.
     */
    public function generateTrackingToken(string $recipientEmail, ?EmailCampaign $campaign = null): string
    {
        $token = Str::random(32);

        EmailTracking::create([
            'tenant_id' => $this->tenantContext->getTenantId(),
            'email_campaign_id' => $campaign?->id,
            'recipient_email' => $recipientEmail,
            'tracking_token' => $token,
            'opened' => false,
            'open_count' => 0,
        ]);

        return $token;
    }

    /**
     * Get tracking pixel URL.
     */
    protected function getTrackingPixelUrl(string $token): string
    {
        return route('api.crm.email-tracking.open', ['token' => $token]);
    }

    /**
     * Replace all links in email body with tracking links.
     */
    protected function replaceLinksWithTracking(string $body, string $token): string
    {
        // Pattern to match href links
        $pattern = '/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is';
        
        return preg_replace_callback($pattern, function ($matches) use ($token) {
            $originalUrl = $matches[1];
            $linkText = $matches[2];
            
            // Skip if already a tracking URL or mailto: link
            if (strpos($originalUrl, 'mailto:') === 0 || strpos($originalUrl, route('api.crm.email-tracking.click')) === 0) {
                return $matches[0];
            }

            // Create tracking URL
            $trackingUrl = route('api.crm.email-tracking.click', [
                'token' => $token,
                'url' => urlencode($originalUrl),
            ]);

            return '<a href="' . $trackingUrl . '">' . $linkText . '</a>';
        }, $body);
    }

    /**
     * Record email open.
     */
    public function recordOpen(string $token): bool
    {
        $tracking = EmailTracking::where('tracking_token', $token)
            ->where('tenant_id', $this->tenantContext->getTenantId())
            ->first();

        if (!$tracking) {
            return false;
        }

        $tracking->update([
            'opened' => true,
            'opened_at' => $tracking->opened_at ?? now(),
            'open_count' => $tracking->open_count + 1,
        ]);

        // Update campaign stats
        if ($tracking->email_campaign_id) {
            $campaign = EmailCampaign::find($tracking->email_campaign_id);
            if ($campaign && !$tracking->opened_at) {
                $campaign->increment('opened_count');
            }
        }

        return true;
    }

    /**
     * Record email click.
     */
    public function recordClick(string $token, string $url): ?string
    {
        $tracking = EmailTracking::where('tracking_token', $token)
            ->where('tenant_id', $this->tenantContext->getTenantId())
            ->first();

        if (!$tracking) {
            return null;
        }

        $clickedLinks = $tracking->clicked_links ?? [];
        
        if (!in_array($url, $clickedLinks)) {
            $clickedLinks[] = $url;
        }

        $tracking->update([
            'clicked_links' => $clickedLinks,
            'first_clicked_at' => $tracking->first_clicked_at ?? now(),
        ]);

        // Update campaign stats
        if ($tracking->email_campaign_id) {
            $campaign = EmailCampaign::find($tracking->email_campaign_id);
            if ($campaign && !$tracking->first_clicked_at) {
                $campaign->increment('clicked_count');
            }
        }

        return urldecode($url);
    }

    /**
     * Record email bounce.
     */
    public function recordBounce(string $recipientEmail, ?EmailCampaign $campaign, string $reason): void
    {
        $tracking = EmailTracking::where('recipient_email', $recipientEmail)
            ->where('tenant_id', $this->tenantContext->getTenantId())
            ->when($campaign, fn($q) => $q->where('email_campaign_id', $campaign->id))
            ->first();

        if ($tracking) {
            $tracking->update([
                'bounced' => true,
                'bounce_reason' => $reason,
            ]);
        } else {
            EmailTracking::create([
                'tenant_id' => $this->tenantContext->getTenantId(),
                'email_campaign_id' => $campaign?->id,
                'recipient_email' => $recipientEmail,
                'tracking_token' => Str::random(32),
                'bounced' => true,
                'bounce_reason' => $reason,
            ]);
        }

        // Update campaign stats
        if ($campaign) {
            $campaign->increment('bounced_count');
        }
    }
}



























