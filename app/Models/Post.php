<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'image_url',
        'scheduled_time',
        'status',
        'user_id',
    ];

    protected $casts = [
        'scheduled_time' => 'datetime',
        'status' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(Platform::class, 'post_platform')
            ->withPivot('platform_status')
            ->withTimestamps();
    }

    protected static function booted()
    {
        static::created(function ($post) {
            $userId = $post->user_id;
            $date = $post->scheduled_time ? Carbon::parse($post->scheduled_time)->toDateString() : null;
            Cache::forget("user_posts_{$userId}");
            Cache::forget("user_analytics_{$userId}");
            if ($date) {
                Cache::forget("user_post_count_{$userId}_{$date}");
            }
            Cache::forget('due_posts');
        });

        static::updated(function ($post) {
            $userId = $post->user_id;
            Cache::forget("user_posts_{$userId}");
            Cache::forget("user_analytics_{$userId}");
            Cache::forget('due_posts');
        });

        static::deleted(function ($post) {
            $userId = $post->user_id;
            $date = $post->scheduled_time ? Carbon::parse($post->scheduled_time)->toDateString() : null;
            Cache::forget("user_posts_{$userId}");
            Cache::forget("user_analytics_{$userId}");
            if ($date) {
                Cache::forget("user_post_count_{$userId}_{$date}");
            }
            Cache::forget('due_posts');
        });
    }
}
