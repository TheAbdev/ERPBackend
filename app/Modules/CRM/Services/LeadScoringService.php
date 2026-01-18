<?php

namespace App\Modules\CRM\Services;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\Lead;
use App\Modules\CRM\Models\LeadScore;
use App\Modules\CRM\Models\Activity;
use Illuminate\Support\Facades\DB;

class LeadScoringService
{
    protected TenantContext $tenantContext;

    /**
     * Default scoring rules (can be customized per tenant).
     */
    protected array $defaultRules = [
        'contact_info' => [
            'email' => 10,
            'phone' => 10,
        ],
        'source' => [
            'website' => 20,
            'referral' => 30,
            'social_media' => 15,
            'email_campaign' => 15,
            'cold_call' => 5,
        ],
        'status' => [
            'qualified' => 20,
            'contacted' => 15,
            'new' => 5,
        ],
        'activities' => [
            'call' => 5,
            'meeting' => 15,
            'task_completed' => 10,
            'email_sent' => 3,
            'email_opened' => 5,
            'email_clicked' => 10,
        ],
        'engagement' => [
            'recent_activity_days' => 7,
            'recent_activity_bonus' => 10,
            'high_activity_threshold' => 5,
            'high_activity_bonus' => 15,
        ],
    ];

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Get scoring rules (can be customized per tenant).
     */
    protected function getRules(): array
    {
        $tenant = \App\Core\Models\Tenant::find($this->tenantContext->getTenantId());
        $customRules = $tenant->settings['lead_scoring_rules'] ?? null;
        
        return $customRules ? array_merge($this->defaultRules, $customRules) : $this->defaultRules;
    }

    /**
     * Calculate and update lead score.
     */
    public function calculateScore(Lead $lead): int
    {
        $rules = $this->getRules();
        $score = 0;
        $breakdown = [];

        // Contact info scoring
        if ($lead->email && isset($rules['contact_info']['email'])) {
            $score += $rules['contact_info']['email'];
            $breakdown['email'] = $rules['contact_info']['email'];
        }

        if ($lead->phone && isset($rules['contact_info']['phone'])) {
            $score += $rules['contact_info']['phone'];
            $breakdown['phone'] = $rules['contact_info']['phone'];
        }

        // Source scoring
        if ($lead->source && isset($rules['source'][$lead->source])) {
            $score += $rules['source'][$lead->source];
            $breakdown['source'] = $rules['source'][$lead->source];
        }

        // Status scoring
        if ($lead->status && isset($rules['status'][$lead->status])) {
            $score += $rules['status'][$lead->status];
            $breakdown['status'] = $rules['status'][$lead->status];
        }

        // Activity-based scoring
        $activityScore = $this->calculateActivityScore($lead, $rules);
        if ($activityScore > 0) {
            $score += $activityScore;
            $breakdown['activities'] = $activityScore;
        }

        // Engagement scoring
        $engagementScore = $this->calculateEngagementScore($lead, $rules);
        if ($engagementScore > 0) {
            $score += $engagementScore;
            $breakdown['engagement'] = $engagementScore;
        }

        // Update lead score
        $lead->update([
            'score' => $score,
            'score_calculated_at' => now(),
        ]);

        // Save score history
        LeadScore::create([
            'tenant_id' => $this->tenantContext->getTenantId(),
            'lead_id' => $lead->id,
            'score' => $score,
            'score_breakdown' => $breakdown,
            'calculated_at' => now(),
        ]);

        return $score;
    }

    /**
     * Calculate score based on activities.
     */
    protected function calculateActivityScore(Lead $lead, array $rules): int
    {
        $score = 0;
        $activityRules = $rules['activities'] ?? [];

        // Get all activities for this lead
        $activities = Activity::where('tenant_id', $this->tenantContext->getTenantId())
            ->where('related_type', Lead::class)
            ->where('related_id', $lead->id)
            ->get();

        foreach ($activities as $activity) {
            // Score by activity type
            if (isset($activityRules[$activity->type])) {
                $score += $activityRules[$activity->type];
            }

            // Bonus for completed tasks
            if ($activity->type === 'task' && $activity->status === 'completed') {
                $score += $activityRules['task_completed'] ?? 0;
            }
        }

        return $score;
    }

    /**
     * Calculate engagement score based on recent activity.
     */
    protected function calculateEngagementScore(Lead $lead, array $rules): int
    {
        $score = 0;
        $engagementRules = $rules['engagement'] ?? [];

        $recentDays = $engagementRules['recent_activity_days'] ?? 7;
        $recentDate = now()->subDays($recentDays);

        // Count recent activities
        $recentActivities = Activity::where('tenant_id', $this->tenantContext->getTenantId())
            ->where('related_type', Lead::class)
            ->where('related_id', $lead->id)
            ->where('created_at', '>=', $recentDate)
            ->count();

        // Bonus for recent activity
        if ($recentActivities > 0 && isset($engagementRules['recent_activity_bonus'])) {
            $score += $engagementRules['recent_activity_bonus'];
        }

        // Bonus for high activity
        $highActivityThreshold = $engagementRules['high_activity_threshold'] ?? 5;
        if ($recentActivities >= $highActivityThreshold && isset($engagementRules['high_activity_bonus'])) {
            $score += $engagementRules['high_activity_bonus'];
        }

        return $score;
    }

    /**
     * Recalculate scores for all leads.
     */
    public function recalculateAll(): void
    {
        Lead::where('tenant_id', $this->tenantContext->getTenantId())
            ->chunk(100, function ($leads) {
                foreach ($leads as $lead) {
                    $this->calculateScore($lead);
                }
            });
    }
}

