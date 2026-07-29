<?php

namespace App\Http\Requests\TrialClass;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrialClassRequest extends FormRequest
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
            'participant_id' => ['required', 'string', 'exists:participants,uuid'],
            'trial_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }
}
