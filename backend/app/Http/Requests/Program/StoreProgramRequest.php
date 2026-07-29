<?php

namespace App\Http\Requests\Program;

use App\Models\Program;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgramRequest extends FormRequest
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
            'age_group' => ['nullable', 'string', 'max:30'],
            'skill_level' => ['nullable', 'string', 'max:20'],
            'target_competency' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in([Program::STATUS_ACTIVE, Program::STATUS_INACTIVE])],
        ];
    }
}
