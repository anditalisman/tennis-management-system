<?php

namespace App\Http\Requests\TrainingSchedule;

use App\Models\TrainingSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrainingScheduleRequest extends FormRequest
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
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'court_id' => ['required', 'integer', 'exists:courts,id'],
            'coach_id' => ['required', 'integer', 'exists:coaches,id'],
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'type' => ['nullable', Rule::in([TrainingSchedule::TYPE_REGULAR, TrainingSchedule::TYPE_SPECIAL, TrainingSchedule::TYPE_REPLACEMENT])],
        ];
    }
}
