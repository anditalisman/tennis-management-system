<?php

namespace App\Http\Requests\Invoice;

use App\Models\InvoiceItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
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
            'due_date' => ['required', 'date'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'voucher_code' => ['nullable', 'string', 'exists:vouchers,code'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', Rule::in([InvoiceItem::TYPE_PACKAGE, InvoiceItem::TYPE_OTHER])],
            'items.*.package_id' => ['required_if:items.*.item_type,package', 'integer', 'exists:packages,id'],
            'items.*.description' => ['required_if:items.*.item_type,other', 'string', 'max:255'],
            'items.*.unit_price' => ['required_if:items.*.item_type,other', 'numeric', 'min:0'],
            'items.*.qty' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
