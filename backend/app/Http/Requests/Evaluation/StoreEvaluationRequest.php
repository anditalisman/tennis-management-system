<?php

namespace App\Http\Requests\Evaluation;

use App\Models\Evaluation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationRequest extends FormRequest
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
            'participant_id' => ['required', 'string', 'exists:participants,uuid'],
            'coach_id' => ['nullable', 'integer', 'exists:coaches,id'],
            'evaluation_date' => ['required', 'date', 'before_or_equal:today'],
            'next_target' => ['nullable', 'string'],
            'recommended_class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'details' => ['required', 'array', 'min:1', 'max:'.count(Evaluation::ASPECTS)],
            'details.*.aspect' => ['required', Rule::in(Evaluation::ASPECTS), 'distinct'],
            'details.*.score' => ['required', 'integer', 'between:1,5'],
            'details.*.note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
