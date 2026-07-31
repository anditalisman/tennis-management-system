<?php

namespace App\Http\Requests\Package;

use App\Models\Package;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePackageRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'session_count' => ['sometimes', 'required', 'integer', 'min:1'],
            'validity_days' => ['nullable', 'integer', 'min:1'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'type' => ['sometimes', 'required', Rule::in([Package::TYPE_PRIVATE, Package::TYPE_KELOMPOK, Package::TYPE_KORPORAT])],
            'status' => ['nullable', Rule::in([Package::STATUS_ACTIVE, Package::STATUS_INACTIVE])],
        ];
    }
}
