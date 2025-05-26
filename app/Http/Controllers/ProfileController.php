<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/profile",
     *     summary="Get authenticated user profile",
     *     tags={"Profile"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Profile retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function show()
    {
        $user = Auth::user();

        return ApiResponse::success(Response::HTTP_OK, 'Profile retrieved successfully', [
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    //    /**
    //     * @OA\Put(
    //     *     path="/api/profile",
    //     *     summary="Update authenticated user profile",
    //     *     tags={"Profile"},
    //     *     security={{"bearerAuth":{}}},
    //     *     @OA\RequestBody(
    //     *         required=true,
    //     *         @OA\JsonContent(ref="#/components/schemas/UpdateProfileRequest")
    //     *     ),
    //     *     @OA\Response(
    //     *         response=200,
    //     *         description="Profile updated successfully",
    //     *         @OA\JsonContent(
    //     *             @OA\Property(property="name", type="string"),
    //     *             @OA\Property(property="email", type="string", format="email")
    //     *         )
    //     *     ),
    //     *     @OA\Response(
    //     *         response=422,
    //     *         description="Validation error or incorrect password"
    //     *     )
    //     * )
    //     */
    public function update(UpdateProfileRequest $request)
    {
        $user = Auth::user();

        $validated = $request->validated();

        if (isset($validated['password']) && ! Hash::check($validated['old_password'], $user->password)) {
            return ApiResponse::error(Response::HTTP_UNPROCESSABLE_ENTITY, 'Current password is incorrect', [
                'old_password' => ['Current password is incorrect'],
            ]);
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        return ApiResponse::success(Response::HTTP_OK, 'Profile updated successfully', [
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    //    /**
    //     * @OA\Delete(
    //     *     path="/api/profile",
    //     *     summary="Delete authenticated user profile",
    //     *     tags={"Profile"},
    //     *     security={{"bearerAuth":{}}},
    //     *     @OA\Response(
    //     *         response=200,
    //     *         description="Profile deleted successfully"
    //     *     ),
    //     *     @OA\Response(
    //     *         response=401,
    //     *         description="Unauthenticated"
    //     *     )
    //     * )
    //     */
    public function destroy()
    {
        $user = Auth::user();
        Log::info('Deleting profile', ['user_id' => $user->id]);

        // Delete associated posts and their images
        $posts = $user->posts;
        foreach ($posts as $post) {
            if ($post->image_url) {
                Storage::disk('public')->delete($post->image_url);
            }
            $post->platforms()->detach();
            $post->delete();
        }

        $user->delete();
        Log::info('Profile deleted', ['user_id' => $user->id]);

        return ApiResponse::success(Response::HTTP_OK, 'Profile deleted successfully');
    }
}
