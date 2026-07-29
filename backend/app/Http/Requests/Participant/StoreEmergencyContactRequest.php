<?php

namespace App\Http\Requests\Participant;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmergencyContactRequest extends FormRequest
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
            'relation' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:30'],
        ];
    }
}
