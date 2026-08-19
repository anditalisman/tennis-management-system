<?php

namespace App\Http\Requests\Tournament;

use App\Models\TournamentParticipant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTournamentParticipantRequest extends FormRequest
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
            'category' => [
                'required',
                Rule::in([TournamentParticipant::CATEGORY_BEGINNER, TournamentParticipant::CATEGORY_UPPER_BEGINNER]),
            ],
        ];
    }
}
