<?php

namespace App\Http\Requests\TrainingSchedule;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleTrainingScheduleRequest extends FormRequest
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
            'court_id' => ['required', 'integer', 'exists:courts,id'],
            'coach_id' => ['required', 'integer', 'exists:coaches,id'],
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }
}
