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
    public function show()
    {
        $user = Auth::user();
        Log::info('Fetching profile', ['user_id' => $user->id]);

        return ApiResponse::success(Response::HTTP_OK, 'Profile retrieved successfully', [
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = Auth::user();
        Log::info('Updating profile', ['user_id' => $user->id, 'request' => $request->all()]);

        $validated = $request->validated();

        if (isset($validated['password']) && !Hash::check($validated['old_password'], $user->password)) {
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

        Log::info('Profile updated', ['user_id' => $user->id]);

        return ApiResponse::success(Response::HTTP_OK, 'Profile updated successfully', [
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function destroy()
    {
        $user = Auth::user();
        Log::info('Deleting profile', ['user_id' => $user->id]);

        // Delete associated posts and their images
        $posts = $user->posts;
        foreach ($posts as $post) {
            if ($post->image_url) {
                Storage::disk('public')->delete($post->image_url);
                Log::info('Deleted post image', ['path' => $post->image_url]);
            }
            $post->platforms()->detach();
            $post->delete();
        }

        $user->delete();
        Log::info('Profile deleted', ['user_id' => $user->id]);

        return ApiResponse::success(Response::HTTP_OK, 'Profile deleted successfully');
    }
}