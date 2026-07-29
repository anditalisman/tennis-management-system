<?php

namespace App\Http\Requests\Coach;

use App\Models\Coach;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCoachRequest extends FormRequest
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
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'certifications' => ['nullable', 'array'],
            'bio' => ['nullable', 'string'],
            'employment_status' => ['sometimes', Rule::in([Coach::STATUS_ACTIVE, Coach::STATUS_INACTIVE])],
        ];
    }
}
