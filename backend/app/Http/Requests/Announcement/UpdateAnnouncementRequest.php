<?php

namespace App\Http\Requests\Announcement;

use App\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnnouncementRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'body' => ['sometimes', 'required', 'string'],
            'target_type' => ['nullable', Rule::in([Announcement::TARGET_ALL, Announcement::TARGET_BRANCH, Announcement::TARGET_ROLE])],
            'target_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in([Announcement::STATUS_DRAFT, Announcement::STATUS_PUBLISHED])],
            'publish_at' => ['nullable', 'date'],
            'expire_at' => ['nullable', 'date', 'after:publish_at'],
        ];
    }
}
