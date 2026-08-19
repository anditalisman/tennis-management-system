<?php

namespace App\Http\Requests\Tournament;

use App\Models\TournamentParticipant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTournamentParticipantRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'max:150'],
            'nickname' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:30'],
            'category' => [
                'required',
                Rule::in([TournamentParticipant::CATEGORY_BEGINNER, TournamentParticipant::CATEGORY_UPPER_BEGINNER]),
            ],
        ];
    }
}
