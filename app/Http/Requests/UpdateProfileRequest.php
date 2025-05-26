<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * @OA\Schema(
 *     schema="UpdateProfileRequest",
 *     required={"name", "email"},
 *
 *     @OA\Property(property="name", type="string", maxLength=255, example="Jane Doe"),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255, example="janedoe@example.com"),
 *     @OA\Property(property="old_password", type="string", nullable=true, example="OldPassword123!"),
 *     @OA\Property(property="password", type="string", nullable=true, minLength=8, example="NewPassword123!"),
 *     @OA\Property(property="password_confirmation", type="string", nullable=true, minLength=8, example="NewPassword123!")
 * )
 */
class UpdateProfileRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$this->user()->id],
            'old_password' => ['nullable', 'string', 'required_with:password'],
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'password_confirmation' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'old_password.required_with' => 'Current password is required to set a new password.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 8 characters.',
        ];
    }
}
