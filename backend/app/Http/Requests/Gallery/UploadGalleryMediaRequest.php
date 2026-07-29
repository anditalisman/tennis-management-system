<?php

namespace App\Http\Requests\Gallery;

use Illuminate\Foundation\Http\FormRequest;

class UploadGalleryMediaRequest extends FormRequest
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
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,mp4,mov', 'max:51200'],
        ];
    }
}
