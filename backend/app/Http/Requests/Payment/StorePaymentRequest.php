<?php

namespace App\Http\Requests\Payment;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
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
            'method' => ['required', Rule::in([Payment::METHOD_TRANSFER, Payment::METHOD_CASH, Payment::METHOD_QRIS])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }
}
