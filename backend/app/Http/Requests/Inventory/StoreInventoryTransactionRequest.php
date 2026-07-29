<?php

namespace App\Http\Requests\Inventory;

use App\Models\InventoryTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryTransactionRequest extends FormRequest
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
            'type' => ['required', Rule::in([
                InventoryTransaction::TYPE_IN,
                InventoryTransaction::TYPE_OUT,
                InventoryTransaction::TYPE_BORROW,
                InventoryTransaction::TYPE_RETURN,
                InventoryTransaction::TYPE_DAMAGE,
                InventoryTransaction::TYPE_LOSS,
            ])],
            'qty' => ['required', 'integer', 'min:1'],
            'participant_id' => ['nullable', 'integer', 'exists:participants,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
