<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 * *     schema="PlatformToggleRequest",
 * *     type="object",
 * *     required={"platform_id", "active"},
 *
 * *     @OA\Property(property="platform_id", type="integer", example=2),
 * *     @OA\Property(property="active", type="boolean", example=true)
 * * )
 */
class PlatformToggleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'platform_id' => ['required', 'integer', 'exists:platforms,id'],
            'active' => ['required', 'boolean'],
        ];
    }
}
