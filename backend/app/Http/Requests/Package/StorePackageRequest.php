<?php

namespace App\Http\Requests\Package;

use App\Models\Package;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePackageRequest extends FormRequest
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
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'name' => ['required', 'string', 'max:150'],
            'session_count' => ['required', 'integer', 'min:1'],
            'validity_days' => ['nullable', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'type' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', Rule::in([Package::STATUS_ACTIVE, Package::STATUS_INACTIVE])],
        ];
    }
}
