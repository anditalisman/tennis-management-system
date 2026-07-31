<?php

namespace App\Http\Requests\Package;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutPackageRequest extends FormRequest
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
            // Only required for a guardian checking out on behalf of one of
            // their children — a participant checking out for themselves
            // doesn't send this at all. Enforced in the controller since it
            // depends on which role is calling, not on the payload shape.
            'participant_id' => ['nullable', 'string', 'exists:participants,uuid'],
            'voucher_code' => ['nullable', 'string', 'exists:vouchers,code'],
        ];
    }
}
