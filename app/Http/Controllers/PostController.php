<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\CreatePostRequest;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::where('user_id', Auth::user()->id)
            ->with(['platforms' => function ($query) {
                $query->select('platforms.id', 'platforms.name', 'platforms.type', 'post_platform.platform_status');
            }])
            ->get();

        return ApiResponse::success(Response::HTTP_OK, 'Posts retrieved successfully', $posts);
    }

    public function store(CreatePostRequest $request)
    {
        $validated = $request->validated();

        $dailyPosts = Post::where('user_id', Auth::id())
            ->whereDate('created_at', today())
            ->count();
        if ($dailyPosts >= 10) {
            return response()->json(['message' => 'Daily post limit reached'], 429);
        }

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = $request->file('image')->store('images', 'public');
        }

        $post = Post::create([
            'title' => $validated->title,
            'content' => $validated->content,
            'image' => $imageUrl,
            'scheduled_time' => $validated->scheduled_time,
            'status' => 'scheduled',
            'user_id' => Auth::id(),
        ]);

        $post->platforms()->attach($validated->platforms, ['platform_status' => 'pending']);

        return ApiResponse::success(Response::HTTP_CREATED, 'Post created successfully', [
            'post' => $post,
            'platforms' => $post->platforms,
        ]);
    }
}