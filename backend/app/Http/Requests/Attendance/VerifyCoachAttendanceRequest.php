<?php

namespace App\Http\Requests\Attendance;

use App\Models\CoachAttendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyCoachAttendanceRequest extends FormRequest
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
            'status' => ['required', Rule::in([CoachAttendance::STATUS_PRESENT, CoachAttendance::STATUS_LATE, CoachAttendance::STATUS_ABSENT])],
        ];
    }
}
