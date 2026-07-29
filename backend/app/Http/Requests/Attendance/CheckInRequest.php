<?php

namespace App\Http\Requests\Attendance;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckInRequest extends FormRequest
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
            'participant_id' => ['nullable', 'string', 'exists:participants,uuid'],
            'method' => ['nullable', Rule::in([Attendance::METHOD_QR, Attendance::METHOD_MANUAL])],
        ];
    }
}
