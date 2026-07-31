<?php

namespace App\Http\Requests\PaymentMethod;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentMethodRequest extends FormRequest
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
            'type' => ['sometimes', 'required', Rule::in([
                PaymentMethod::TYPE_QRIS,
                PaymentMethod::TYPE_BANK_TRANSFER,
                PaymentMethod::TYPE_CASH,
                PaymentMethod::TYPE_OTHER,
            ])],
            'label' => ['sometimes', 'required', 'string', 'max:150'],
            'details' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:10240'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
