<?php

namespace App\Http\Requests\Branch;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
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
        $branch = $this->route('branch');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'slug' => ['sometimes', 'required', 'string', 'max:150', 'alpha_dash', Rule::unique('branches', 'slug')->ignore($branch)],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'geo_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'geo_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['nullable', Rule::in([Branch::STATUS_ACTIVE, Branch::STATUS_INACTIVE])],
        ];
    }
}
