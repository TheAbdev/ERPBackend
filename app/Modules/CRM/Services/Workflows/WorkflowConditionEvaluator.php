<?php

namespace App\Modules\CRM\Services\Workflows;

class WorkflowConditionEvaluator
{
    /**
     * Evaluate all conditions against the trigger data.
     *
     * @param  array  $conditions
     * @param  mixed  $triggerData
     * @return bool
     */
    public function evaluate(array $conditions, $triggerData): bool
    {
        if (empty($conditions)) {
            return true; // No conditions means always true
        }

        foreach ($conditions as $condition) {
            if (! $this->evaluateCondition($condition, $triggerData)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition.
     *
     * @param  array  $condition
     * @param  mixed  $triggerData
     * @return bool
     */
    protected function evaluateCondition(array $condition, $triggerData): bool
    {
        $type = $condition['type'] ?? null;
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? null;
        $value = $condition['value'] ?? null;

        if (! $type || ! $field) {
            return false;
        }

        return match ($type) {
            'status_change' => $this->evaluateStatusChange($condition, $triggerData),
            'date_reached' => $this->evaluateDateReached($condition, $triggerData),
            'value_threshold' => $this->evaluateValueThreshold($condition, $triggerData),
            'field_equals' => $this->evaluateFieldEquals($field, $operator, $value, $triggerData),
            default => false,
        };
    }

    /**
     * Evaluate status change condition.
     *
     * @param  array  $condition
     * @param  mixed  $triggerData
     * @return bool
     */
    protected function evaluateStatusChange(array $condition, $triggerData): bool
    {
        $fromStatus = $condition['from_status'] ?? null;
        $toStatus = $condition['to_status'] ?? null;

        $actualFromStatus = $triggerData['old_status'] ?? null;
        $actualToStatus = $triggerData['new_status'] ?? $triggerData['status'] ?? null;

        if ($fromStatus && $actualFromStatus !== $fromStatus) {
            return false;
        }

        if ($toStatus && $actualToStatus !== $toStatus) {
            return false;
        }

        return true;
    }

    /**
     * Evaluate date reached condition.
     *
     * @param  array  $condition
     * @param  mixed  $triggerData
     * @return bool
     */
    protected function evaluateDateReached(array $condition, $triggerData): bool
    {
        $dateField = $condition['date_field'] ?? 'due_date';
        $comparison = $condition['comparison'] ?? 'equals'; // equals, before, after

        $entity = $triggerData['entity'] ?? null;
        if (! $entity || ! isset($entity->$dateField)) {
            return false;
        }

        $entityDate = $entity->$dateField;
        if (! $entityDate) {
            return false;
        }

        // Ensure entityDate is a Carbon instance
        if (! $entityDate instanceof \Carbon\Carbon) {
            $entityDate = \Carbon\Carbon::parse($entityDate);
        }

        $targetDate = now();
        if (isset($condition['target_date'])) {
            $targetDate = is_string($condition['target_date'])
                ? \Carbon\Carbon::parse($condition['target_date'])
                : $condition['target_date'];
        }

        if (! $targetDate instanceof \Carbon\Carbon) {
            $targetDate = \Carbon\Carbon::parse($targetDate);
        }

        return match ($comparison) {
            'equals' => $entityDate->isSameDay($targetDate),
            'before' => $entityDate->isBefore($targetDate),
            'after' => $entityDate->isAfter($targetDate),
            'overdue' => $entityDate->isPast(),
            default => false,
        };
    }

    /**
     * Evaluate value threshold condition.
     *
     * @param  array  $condition
     * @param  mixed  $triggerData
     * @return bool
     */
    protected function evaluateValueThreshold(array $condition, $triggerData): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? 'gte'; // gte, lte, equals, gt, lt
        $threshold = $condition['threshold'] ?? 0;

        $entity = $triggerData['entity'] ?? null;
        if (! $entity || ! isset($entity->$field)) {
            return false;
        }

        $fieldValue = $entity->$field;

        return match ($operator) {
            'gte' => $fieldValue >= $threshold,
            'lte' => $fieldValue <= $threshold,
            'equals' => $fieldValue == $threshold,
            'gt' => $fieldValue > $threshold,
            'lt' => $fieldValue < $threshold,
            default => false,
        };
    }

    /**
     * Evaluate field equals condition.
     *
     * @param  string  $field
     * @param  string  $operator
     * @param  mixed  $value
     * @param  mixed  $triggerData
     * @return bool
     */
    protected function evaluateFieldEquals(string $field, string $operator, $value, $triggerData): bool
    {
        $entity = $triggerData['entity'] ?? null;
        if (! $entity || ! isset($entity->$field)) {
            return false;
        }

        $fieldValue = $entity->$field;

        return match ($operator) {
            'equals' => $fieldValue == $value,
            'not_equals' => $fieldValue != $value,
            'contains' => is_string($fieldValue) && str_contains($fieldValue, $value),
            'in' => in_array($fieldValue, (array) $value),
            'not_in' => ! in_array($fieldValue, (array) $value),
            default => false,
        };
    }
}

