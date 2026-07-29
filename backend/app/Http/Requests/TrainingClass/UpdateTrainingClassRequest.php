<?php

namespace App\Http\Requests\TrainingClass;

use App\Models\TrainingClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrainingClassRequest extends FormRequest
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
            'coach_id' => ['nullable', 'integer', 'exists:coaches,id'],
            'court_id' => ['nullable', 'integer', 'exists:courts,id'],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'capacity_min' => ['nullable', 'integer', 'min:1'],
            'capacity_max' => ['sometimes', 'required', 'integer', 'min:1'],
            'session_duration' => ['nullable', 'integer', 'min:15', 'max:240'],
            'status' => ['nullable', Rule::in([TrainingClass::STATUS_ACTIVE, TrainingClass::STATUS_INACTIVE])],
        ];
    }
}
