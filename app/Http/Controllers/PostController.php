<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\CreatePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $status = $request->input('status', 'all');
        $date = $request->input('date', 'all');
        $cacheKey = "user_posts_{$userId}_{$status}_{$date}";

        $posts = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($userId, $status, $date) {
            $query = Post::where('user_id', $userId)
                ->with(['platforms' => function ($query) {
                    $query->select('platforms.id', 'platforms.name', 'platforms.type', 'post_platform.platform_status');
                }]);

            if ($status !== 'all' && in_array(strtolower($status), ['scheduled', 'published', 'failed'])) {
                $query->where('status', strtolower($status));
            }

            if ($date !== 'all') {
                $query->whereDate('scheduled_time', Carbon::parse($date)->toDateString());
            }

            return $query->get();
        });

        return ApiResponse::success(Response::HTTP_OK, 'Posts retrieved successfully', $posts);
    }

    public function show($id)
    {
        $userId = Auth::id();
        $cacheKey = "post_{$userId}_{$id}";

        $post = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($userId, $id) {
            return Post::where('user_id', $userId)
                ->with(['platforms' => function ($query) {
                    $query->select('platforms.id', 'platforms.name', 'platforms.type', 'post_platform.platform_status');
                }])
                ->findOrFail($id);
        });

        return ApiResponse::success(Response::HTTP_OK, 'Post retrieved successfully', $post);
    }

    public function store(CreatePostRequest $request)
    {
        $validated = $request->validated();
        $userId = Auth::id();

        if ($validated['scheduled_time']) {
            $date = Carbon::parse($validated['scheduled_time'])->toDateString();
            $cacheKey = "user_post_count_{$userId}_{$date}";

            $dailyPosts = Cache::remember($cacheKey, now()->endOfDay(), function () use ($userId, $date) {
                return Post::where('user_id', $userId)
                    ->whereDate('scheduled_time', $date)
                    ->count();
            });

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
            'user_id' => $userId,
        ]);

        $post->platforms()->attach($validated['platforms'], ['platform_status' => 'pending']);

        // Invalidate daily post count cache
        Cache::forget("user_post_count_{$userId}_{$date}");

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
        if ($post->status === 'published') {
            return ApiResponse::error(Response::HTTP_FORBIDDEN, 'Cannot delete a published post');
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

        $userId = Auth::id();
        $cacheKey = "user_analytics_{$userId}";

        $analytics = Cache::remember($cacheKey, now()->addHours(1), function () use ($userId) {
            $posts = Post::where('user_id', $userId)
                ->with(['platforms' => function ($query) {
                    $query->select('platforms.id', 'platforms.name', 'platforms.type', 'post_platform.platform_status');
                }])
                ->get();

            $postsPerPlatform = $posts->flatMap->platforms
                ->groupBy('name')
                ->map->count();

            $totalPosts = $posts->count();
            $publishedPosts = $posts->where('status', 'published')->count();
            $successRate = $totalPosts > 0 ? ($publishedPosts / $totalPosts) * 100 : 0;

            return [
                'posts_per_platform' => $postsPerPlatform,
                'success_rate' => round($successRate, 2),
                'scheduled_count' => $posts->where('status', 'scheduled')->count(),
                'published_count' => $publishedPosts,
                'failed_count' => $posts->where('status', 'failed')->count(),
            ];
        });

        return ApiResponse::success(Response::HTTP_OK, 'Analytics data retrieved', $analytics);
    }
}