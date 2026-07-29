<?php

namespace App\Http\Requests\Coach;

use App\Models\Coach;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreCoachRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', Password::min(8)->mixedCase()->numbers()],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'certifications' => ['nullable', 'array'],
            'bio' => ['nullable', 'string'],
            'employment_status' => ['nullable', Rule::in([Coach::STATUS_ACTIVE, Coach::STATUS_INACTIVE])],
        ];
    }
}
