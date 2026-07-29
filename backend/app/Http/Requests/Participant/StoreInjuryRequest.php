<?php

namespace App\Http\Requests\Participant;

use App\Models\Injury;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInjuryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string'],
            'severity' => ['required', Rule::in([Injury::SEVERITY_MINOR, Injury::SEVERITY_MODERATE, Injury::SEVERITY_SEVERE])],
            'reported_at' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
