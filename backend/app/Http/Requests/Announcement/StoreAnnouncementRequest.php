<?php

namespace App\Http\Requests\Announcement;

use App\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // required_unless below needs the real value being validated, not the
        // model-level default that only applies after this request is filled.
        $this->merge(['target_type' => $this->input('target_type', Announcement::TARGET_ALL)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
            'target_type' => [Rule::in([Announcement::TARGET_ALL, Announcement::TARGET_BRANCH, Announcement::TARGET_ROLE])],
            'target_id' => ['nullable', 'integer', 'required_unless:target_type,all'],
            'status' => ['nullable', Rule::in([Announcement::STATUS_DRAFT, Announcement::STATUS_PUBLISHED])],
            'publish_at' => ['nullable', 'date'],
            'expire_at' => ['nullable', 'date', 'after:publish_at'],
        ];
    }
}
