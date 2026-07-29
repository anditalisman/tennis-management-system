<?php

namespace App\Http\Requests\Attendance;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkVerifyAttendanceRequest extends FormRequest
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
            'records' => ['required', 'array', 'min:1'],
            'records.*.participant_id' => ['required', 'string', 'exists:participants,uuid'],
            'records.*.status' => ['required', Rule::in([
                Attendance::STATUS_PRESENT,
                Attendance::STATUS_LATE,
                Attendance::STATUS_ABSENT,
                Attendance::STATUS_EXCUSED,
                Attendance::STATUS_SICK,
                Attendance::STATUS_LEFT_EARLY,
            ])],
        ];
    }
}
