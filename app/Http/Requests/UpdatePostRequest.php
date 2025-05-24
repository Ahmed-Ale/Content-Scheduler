<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'scheduled_time' => 'nullable|date|after:now',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'exists:platforms,id',
            'image' => 'sometimes|nullable|image|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The title is required.',
            'content.required' => 'The content is required.',
            'image.image' => 'The file must be an image.',
            'image.max' => 'The image must not exceed 2MB.',
            'scheduled_time.required' => 'The scheduled time is required.',
            'scheduled_time.date' => 'The scheduled time must be a valid date.',
            'scheduled_time.after' => 'The scheduled time must be in the future.',
            'platforms.required' => 'At least one platform must be selected.',
            'platforms.array' => 'Platforms must be an array.',
            'platforms.min' => 'At least one platform must be selected.',
            'platforms.*.exists' => 'Selected platform is invalid.',
        ];
    }
}
