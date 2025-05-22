<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PlatformUser extends Pivot
{
    protected $table = 'platform_user';

    protected $fillable = [
        'user_id',
        'platform_id',
    ];

    public $timestamps = true;
}
