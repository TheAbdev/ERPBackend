<?php

namespace App\Modules\CRM\Services;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\Lead;
use App\Modules\CRM\Models\LeadAssignmentRule;
use Illuminate\Support\Facades\DB;

class LeadAssignmentService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Auto-assign lead based on rules.
     */
    public function autoAssign(Lead $lead): ?int
    {
        $rules = LeadAssignmentRule::where('tenant_id', $this->tenantContext->getTenantId())
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        foreach ($rules as $rule) {
            if ($this->evaluateConditions($lead, $rule->conditions)) {
                return $this->assignLead($lead, $rule);
            }
        }

        return null;
    }

    /**
     * Evaluate rule conditions.
     */
    protected function evaluateConditions(Lead $lead, array $conditions): bool
    {
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;

            if (! $field) {
                continue;
            }

            $leadValue = $lead->{$field};

            if (! $this->compare($leadValue, $operator, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compare values based on operator.
     */
    protected function compare($leadValue, string $operator, $value): bool
    {
        return match ($operator) {
            'equals' => $leadValue == $value,
            'not_equals' => $leadValue != $value,
            'contains' => str_contains(strtolower($leadValue ?? ''), strtolower($value ?? '')),
            'not_contains' => ! str_contains(strtolower($leadValue ?? ''), strtolower($value ?? '')),
            'greater_than' => $leadValue > $value,
            'less_than' => $leadValue < $value,
            'in' => in_array($leadValue, (array) $value),
            'not_in' => ! in_array($leadValue, (array) $value),
            default => false,
        };
    }

    /**
     * Assign lead based on rule.
     */
    protected function assignLead(Lead $lead, LeadAssignmentRule $rule): ?int
    {
        if ($rule->assignment_type === 'user' && $rule->assigned_user_id) {
            $lead->update(['assigned_to' => $rule->assigned_user_id]);
            return $rule->assigned_user_id;
        }

        if ($rule->assignment_type === 'team' && $rule->assigned_team_id) {
            $team = $rule->assignedTeam;
            if ($team && $team->users()->count() > 0) {
                // Round-robin assignment
                $user = $this->getNextTeamMember($team->id);
                if ($user) {
                    $lead->update(['assigned_to' => $user->id]);
                    return $user->id;
                }
            }
        }

        if ($rule->assignment_type === 'round_robin') {
            $user = $this->getNextUser();
            if ($user) {
                $lead->update(['assigned_to' => $user->id]);
                return $user->id;
            }
        }

        return null;
    }

    /**
     * Get next team member for round-robin.
     */
    protected function getNextTeamMember(int $teamId)
    {
        $tenantId = $this->tenantContext->getTenantId();

        // Get team users
        $team = \App\Core\Models\Team::where('tenant_id', $tenantId)
            ->where('id', $teamId)
            ->with('users')
            ->first();

        if (! $team || $team->users->isEmpty()) {
            return null;
        }

        // Get user with least assigned leads (load balancing)
        $userIds = $team->users->pluck('id')->toArray();
        
        return \App\Models\User::where('tenant_id', $tenantId)
            ->whereIn('id', $userIds)
            ->withCount(['leads' => function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at');
            }])
            ->orderBy('leads_count', 'asc')
            ->first();
    }

    /**
     * Get next user for round-robin (load balancing).
     */
    protected function getNextUser()
    {
        $tenantId = $this->tenantContext->getTenantId();

        // Get user with least assigned leads for load balancing
        return \App\Models\User::where('tenant_id', $tenantId)
            ->withCount(['leads' => function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at');
            }])
            ->orderBy('leads_count', 'asc')
            ->first();
    }
}

