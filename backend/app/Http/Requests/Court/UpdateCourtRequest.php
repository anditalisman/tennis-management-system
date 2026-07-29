<?php

namespace App\Http\Requests\Court;

use App\Models\Court;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourtRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'surface_type' => ['nullable', 'string', 'max:30'],
            'operating_hours' => ['nullable', 'array'],
            'rental_cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in([Court::STATUS_ACTIVE, Court::STATUS_INACTIVE, Court::STATUS_MAINTENANCE])],
        ];
    }
}
