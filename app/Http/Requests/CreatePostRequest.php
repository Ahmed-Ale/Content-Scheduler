<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 * *      schema="CreatePostRequest",
 * *      required={"title", "content", "platforms"},
 *
 * *      @OA\Property(property="title", type="string", maxLength=255, example="My scheduled post"),
 * *      @OA\Property(property="content", type="string", example="This is the post content."),
 * *      @OA\Property(property="scheduled_time", type="string", format="date-time", nullable=true, example="2025-06-01T10:00:00Z"),
 * *      @OA\Property(
 * *          property="platforms",
 * *          type="array",
 *
 * *          @OA\Items(type="integer", example=1),
 * *          description="Array of platform IDs"
 * *      ),
 *
 * *      @OA\Property(
 * *          property="image",
 * *          type="string",
 * *          format="binary",
 * *          nullable=true,
 * *          description="Optional image file (max 2MB)"
 * *      )
 * *  )
 */
class CreatePostRequest extends FormRequest
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
            'image' => 'nullable|image|max:2048',
        ];
    }
}
