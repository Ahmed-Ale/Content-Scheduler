<?php

namespace App\Console\Commands;

use App\Jobs\PublishPostJob;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PublishPosts extends Command
{
    protected $signature = 'posts:publish';
    protected $description = 'Publish scheduled posts that are due';

    public function handle(): void
    {
        Log::info('Running posts:publish command');

        $duePosts = Post::where('status', 'scheduled')
            ->whereNotNull('scheduled_time')
            ->where('scheduled_time', '<=', now())
            ->with('platforms')
            ->get();

        if ($duePosts->isEmpty()) {
            Log::info('No due posts found');
            if ($this->output) {
                $this->info('No due posts to publish.');
            }
            return;
        }

        foreach ($duePosts as $post) {
            PublishPostJob::dispatch($post)->onQueue('publishing');
            Log::info('Dispatched PublishPostJob', ['post_id' => $post->id]);
        }

        $message = "Dispatched {$duePosts->count()} post(s) for publishing.";
        Log::info($message);
        if ($this->output) {
            $this->info($message);
        }
    }
}