<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
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
        $user = $this->route('user');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['sometimes', 'required', Password::min(8)->mixedCase()->numbers()],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'status' => ['sometimes', Rule::in([User::STATUS_ACTIVE, User::STATUS_SUSPENDED, User::STATUS_INACTIVE])],
            'roles' => ['sometimes', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,slug'],
        ];
    }
}
