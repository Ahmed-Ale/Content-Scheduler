<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\CreatePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::where('user_id', Auth::id())
            ->with(['platforms' => function ($query) {
                $query->select('platforms.id', 'platforms.name', 'platforms.type', 'post_platform.platform_status');
            }]);

        if ($request->filled('status') && in_array(strtolower($request->status), ['scheduled', 'published', 'failed'])) {
            $query->where('status', strtolower($request->status));
        }

        if ($request->filled('date')) {
            try {
                $query->whereDate('scheduled_time', Carbon::parse($request->date)->toDateString());
            } catch (\Exception $e) {
                // Invalid date format; skip filter
            }
        }

        $posts = $query->get();

        return ApiResponse::success(Response::HTTP_OK, 'Posts retrieved successfully', $posts);
    }

    public function show($id)
    {
        $post = Post::where('user_id', Auth::id())
            ->with(['platforms' => function ($query) {
                $query->select('platforms.id', 'platforms.name', 'platforms.type', 'post_platform.platform_status');
            }])
            ->findOrFail($id);

        return ApiResponse::success(Response::HTTP_OK, 'Post retrieved successfully', $post);
    }

    public function store(CreatePostRequest $request)
    {
        Log::info($request);
        $validated = $request->validated();

        $dailyPosts = Post::where('user_id', Auth::id())
            ->whereDate('scheduled_time', Carbon::parse($validated['scheduled_time'])->toDateString())
            ->count();
        if ($dailyPosts >= 10) {
            return ApiResponse::error(Response::HTTP_TOO_MANY_REQUESTS, 'Daily post limit reached');
        }

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = $request->file('image')->store('images', 'public');
        }

        $post = Post::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'image_url' => $imageUrl,
            'scheduled_time' => $validated['scheduled_time'],
            'status' => 'scheduled',
            'user_id' => Auth::id(),
        ]);

        $post->platforms()->attach($validated['platforms'], ['platform_status' => 'pending']);

        return ApiResponse::success(Response::HTTP_CREATED, 'Post created successfully', [
            'post' => $post->load('platforms'),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        if ($post->user_id !== Auth::id()) {
            return ApiResponse::error(Response::HTTP_FORBIDDEN, 'Unauthorized to update this post');
        }

        Log::info('Update request data:', $request->all());
        Log::info('Request files:', $request->allFiles());

        $validated = $request->validated();

        $imageUrl = $post->image_url; // Preserve existing image
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            try {
                // Delete old image if it exists
                if ($post->image_url) {
                    Storage::disk('public')->delete($post->image_url);
                    Log::info('Deleted old image:', ['path' => $post->image_url]);
                }
                $imageUrl = $request->file('image')->store('images', 'public');
                Log::info('New image stored successfully:', ['path' => $imageUrl]);
            } catch (\Exception $e) {
                Log::error('Failed to store image:', ['error' => $e->getMessage()]);
                return ApiResponse::error(Response::HTTP_INTERNAL_SERVER_ERROR, 'Failed to store image');
            }
        } else {
            Log::warning('No valid image file uploaded');
        }

        $post->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'image_url' => $imageUrl,
            'scheduled_time' => $validated['scheduled_time'],
            'status' => 'scheduled',
        ]);

        $post->platforms()->sync($validated['platforms'], ['platform_status' => 'pending']);

        return ApiResponse::success(Response::HTTP_OK, 'Post updated successfully', [
            'post' => $post->load('platforms'),
        ]);
    }

    public function destroy(Post $post)
    {
        if ($post->user_id !== Auth::id()) {
            return ApiResponse::error(Response::HTTP_FORBIDDEN, 'Unauthorized to delete this post');
        }

        if ($post->image_url) {
            Storage::disk('public')->delete($post->image_url);
            Log::info('Deleted post image:', ['path' => $post->image_url]);
        }

        $post->platforms()->detach();
        $post->delete();

        return ApiResponse::success(Response::HTTP_OK, 'Post deleted successfully');
    }

    public function analytics()
    {
        if (!Auth::check()) {
            return ApiResponse::error(Response::HTTP_UNAUTHORIZED, 'Unauthorized');
        }
        $posts = Post::where('user_id', Auth::id())
            ->with(['platforms' => function ($query) {
                $query->select('platforms.id', 'platforms.name', 'platforms.type', 'post_platform.platform_status');
            }])
            ->get();

        return ApiResponse::success(Response::HTTP_OK, 'Analytics data retrieved', $posts);
    }
}