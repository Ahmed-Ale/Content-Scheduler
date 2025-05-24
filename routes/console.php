<?php

use App\Console\Commands\PublishPosts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('posts:publish', fn () => app(PublishPosts::class)->handle())
    ->purpose('Publish scheduled posts that are due');

Schedule::command('posts:publish')
    ->everyMinute()
    ->withoutOverlapping()
    ->onSuccess(function () {
        Log::info('Successfully ran posts:publish command');
    })
    ->onFailure(function (\Throwable $e) {
        Log::error('Failed to run posts:publish command', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    })
    ->appendOutputTo(storage_path('logs/scheduler.log'));
