<?php

namespace App\Modules\ERP\Traits;

use App\Modules\ERP\Models\NumberSequence;
use Illuminate\Support\Str;

/**
 * Trait for models that have document numbers generated from number sequences.
 */
trait HasDocumentNumber
{
    /**
     * Generate the next document number from a number sequence.
     *
     * @param  string  $sequenceCode
     * @return string
     */
    public function generateDocumentNumber(string $sequenceCode): string
    {
        $tenantId = $this->tenant_id ?? app(\App\Core\Services\TenantContext::class)->getTenantId();

        $sequence = NumberSequence::where('tenant_id', $tenantId)
            ->where('code', $sequenceCode)
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        if (! $sequence) {
            throw new \Exception("Number sequence '{$sequenceCode}' not found or inactive.");
        }

        // Check if reset is needed
        if ($sequence->reset_period && $sequence->last_reset_date) {
            $shouldReset = match ($sequence->reset_frequency) {
                'yearly' => $sequence->last_reset_date->year < now()->year,
                'monthly' => $sequence->last_reset_date->format('Y-m') < now()->format('Y-m'),
                'daily' => $sequence->last_reset_date->format('Y-m-d') < now()->format('Y-m-d'),
                default => false,
            };

            if ($shouldReset) {
                $sequence->next_number = 1;
                $sequence->last_reset_date = now();
            }
        }

        $number = $sequence->next_number;
        $sequence->next_number++;
        $sequence->save();

        // Format the number
        $formattedNumber = str_pad((string) $number, $sequence->min_length, '0', STR_PAD_LEFT);

        // Build document number
        $documentNumber = $sequence->format ?? '{PREFIX}-{NUMBER}';
        $documentNumber = str_replace('{PREFIX}', $sequence->prefix ?? '', $documentNumber);
        $documentNumber = str_replace('{SUFFIX}', $sequence->suffix ?? '', $documentNumber);
        $documentNumber = str_replace('{NUMBER}', $formattedNumber, $documentNumber);
        $documentNumber = str_replace('{YYYY}', now()->format('Y'), $documentNumber);
        $documentNumber = str_replace('{YY}', now()->format('y'), $documentNumber);
        $documentNumber = str_replace('{MM}', now()->format('m'), $documentNumber);
        $documentNumber = str_replace('{DD}', now()->format('d'), $documentNumber);

        return $documentNumber;
    }
}

