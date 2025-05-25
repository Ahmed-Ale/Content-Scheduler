<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class Platform extends Model
{
    /** @use HasFactory<\Database\Factories\PlatformFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
    ];

    public function jsonSerialize(): object
    {
        return (object) [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'platform_user')->withTimestamps();
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_platform')
            ->withPivot('platform_status')
            ->withTimestamps();
    }

    protected static function booted()
    {
        static::created(function () {
            Cache::forget('platforms_list');
        });

        static::updated(function () {
            Cache::forget('platforms_list');
        });

        static::deleted(function () {
            Cache::forget('platforms_list');
        });
    }
}
