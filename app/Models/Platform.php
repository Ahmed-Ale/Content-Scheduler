<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Platform extends Model
{
    /** @use HasFactory<\Database\Factories\PlatformFactory> */
    use HasFactory;
    protected $fillable = [
        'name',
        'type',
    ];

    public function user(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'platform_user')
            ->withPivot('user_id')
            ->withTimestamps();
    }
    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_platform')
            ->withPivot('platform_status')
            ->withTimestamps();
    }
}
