<?php

namespace App\Http\Requests\Participant;

use App\Models\Participant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreParticipantRequest extends FormRequest
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
        $isSelfRegistration = auth('sanctum')->check();
        $isMinorOrPrestasi = in_array($this->input('age_category'), Participant::GUARDIAN_REQUIRED_CATEGORIES, true);

        return [
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'age_category' => ['required', Rule::in(Participant::AGE_CATEGORIES)],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'skill_level' => ['nullable', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'policy_accepted' => ['required', 'accepted'],
            'referral_code' => ['nullable', 'string', 'exists:referrals,code'],
            // Adult ("dewasa") guests create their own login; minors/prestasi need a guardian instead.
            'password' => [
                Rule::requiredIf(! $isSelfRegistration && ! $isMinorOrPrestasi),
                Password::min(8)->mixedCase()->numbers(),
            ],
            'guardian' => [Rule::requiredIf(! $isSelfRegistration && $isMinorOrPrestasi), 'array'],
            'guardian.name' => ['required_with:guardian', 'string', 'max:150'],
            'guardian.relation' => ['required_with:guardian', 'string', 'max:50'],
            'guardian.phone' => ['required_with:guardian', 'string', 'max:30'],
            'guardian.email' => ['required_with:guardian', 'email', 'max:255'],
            'guardian.password' => ['required_with:guardian', Password::min(8)->mixedCase()->numbers()],
        ];
    }
}
