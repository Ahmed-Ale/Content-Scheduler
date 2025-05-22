<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
}
