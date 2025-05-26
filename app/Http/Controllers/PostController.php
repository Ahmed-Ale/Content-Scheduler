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
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\Response;

class PostController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/posts",
     *     summary="Get all posts of the authenticated user",
     *     tags={"Posts"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by post status (scheduled, published, failed, all)",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Filter by scheduled date (Y-m-d format)",
     *         required=false,
     *
     *         @OA\Schema(type="string", format="date")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Posts retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/posts/{id}",
     *     summary="Get a single post by ID",
     *     tags={"Posts"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Post ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Post retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post not found"
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/posts",
     *     summary="Create a new post",
     *     tags={"Posts"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/CreatePostRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Post created successfully"
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Daily post limit reached"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(CreatePostRequest $request)
    {
        $validated = $request->validated();
        $userId = Auth::id();

        if ($validated['scheduled_time']) {
            $date = Carbon::parse($validated['scheduled_time'])->toDateString();
            $cacheKey = "user_post_count_{$userId}_{$date}";

            $dailyPosts = Post::where('user_id', $userId)
                ->whereDate('scheduled_time', $date)
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
            'user_id' => $userId,
        ]);

        // Attach platforms with validation
        if (! empty($validated['platforms'])) {
            $userPlatforms = Auth::user()->platforms()->pluck('platform_id')->toArray();
            $validPlatforms = array_intersect($validated['platforms'], $userPlatforms);
            if (! empty($validPlatforms)) {
                $post->platforms()->attach($validPlatforms, ['platform_status' => 'pending']);
            }
        }

        Cache::forget("user_post_count_{$userId}_{$date}");
        Cache::forget("user_platforms_{$userId}"); // Ensure platform data is fresh for analytics

        return ApiResponse::success(Response::HTTP_CREATED, 'Post created successfully', [
            'post' => $post->load('platforms'),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/posts/{id}",
     *     summary="Update an existing post",
     *     tags={"Posts"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Post ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/UpdatePostRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Post updated successfully"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to update this post"
     *     )
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/api/posts/{id}",
     *     summary="Delete a post",
     *     tags={"Posts"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Post ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Post deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Cannot delete a published post or unauthorized"
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/posts/analytics",
     *     summary="Get post analytics for the authenticated user",
     *     tags={"Analytics"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Analytics data retrieved"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function analytics()
    {
        if (! Auth::check()) {
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

    /**
     * @OA\Get(
     *     path="/api/posts/analytics/export",
     *     summary="Export post analytics to CSV",
     *     tags={"Analytics"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="CSV export successful",
     *
     *         @OA\MediaType(
     *             mediaType="text/csv"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function exportAnalytics()
    {
        if (! Auth::check()) {
            return response()->json([
                'status' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
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
                'posts_per_platform' => $postsPerPlatform->toArray(),
                'success_rate' => round($successRate, 2),
                'scheduled_count' => $posts->where('status', 'scheduled')->count(),
                'published_count' => $publishedPosts,
                'failed_count' => $posts->where('status', 'failed')->count(),
            ];
        });

        if (empty($analytics['posts_per_platform'])) {
            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'No analytics data available for export',
            ], Response::HTTP_OK);
        }

        $csv = Writer::createFromString();
        $csv->insertOne(['Platform', 'Post Count', 'Success Rate (%)', 'Scheduled', 'Published', 'Failed']);
        foreach ($analytics['posts_per_platform'] as $platform => $count) {
            $csv->insertOne([
                $platform,
                $count,
                $analytics['success_rate'],
                $analytics['scheduled_count'],
                $analytics['published_count'],
                $analytics['failed_count'],
            ]);
        }

        return response($csv->toString(), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="analytics_export_'.now()->format('Y-m-d_H-i-s').'.csv"',
        ]);
    }
}
