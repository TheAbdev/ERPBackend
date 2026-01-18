<?php

namespace App\Modules\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\ERP\Models\JournalEntry;

class PostJournalEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Authorization is handled in the controller method.
     */
    public function authorize(): bool
    {
        // Authorization is handled in JournalEntryController::post() method
        // using $this->authorize('update', $journalEntry)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}

