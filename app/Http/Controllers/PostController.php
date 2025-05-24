<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\CreatePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            $query->whereDate('scheduled_time', Carbon::parse($request->date)->toDateString());
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
        $validated = $request->validated();

        if($validated['scheduled_time']) {
            $dailyPosts = Post::where('user_id', Auth::id())
                ->whereDate('scheduled_time', Carbon::parse($validated['scheduled_time'])->toDateString())
                ->count();
            if ($dailyPosts >= 10) {
                return ApiResponse::error(Response::HTTP_TOO_MANY_REQUESTS, 'Daily post limit reached');
            }
            $validated['status'] = 'scheduled';
        } else {
            $validated['status'] = 'draft';
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
            'status' => $validated['status'],
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

        $validated = $request->validated();

        $imageUrl = $post->image_url;
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            try {
                if ($post->image_url) {
                    Storage::disk('public')->delete($post->image_url);
                }
                $imageUrl = $request->file('image')->store('images', 'public');
            } catch (\Exception $e) {
                return ApiResponse::error(Response::HTTP_INTERNAL_SERVER_ERROR, 'Failed to store image', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $post->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'image_url' => $imageUrl,
            'scheduled_time' => $validated['scheduled_time'],
            'status' => $validated['scheduled_time'] ? 'scheduled' : 'draft',
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