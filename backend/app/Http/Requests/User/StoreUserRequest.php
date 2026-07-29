<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
            'status' => ['nullable', Rule::in([User::STATUS_ACTIVE, User::STATUS_SUSPENDED, User::STATUS_INACTIVE])],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,slug'],
        ];
    }
}
