<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PublishPostJob implements ShouldQueue
{
    use Queueable;

    protected $post;

    /**
     * Create a new job instance.
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Processing post publication', ['post_id' => $this->post->id]);

        $platforms = $this->post->platforms;
        $allSuccessful = true;

        foreach ($platforms as $platform) {
            $platformStatus = $this->publishToPlatform($this->post, $platform);

            $this->post->platforms()->updateExistingPivot($platform->id, [
                'platform_status' => $platformStatus,
            ]);

            if ($platformStatus === 'failed') {
                $allSuccessful = false;
            }

            Log::info('Platform publication result', [
                'post_id' => $this->post->id,
                'platform_id' => $platform->id,
                'platform_name' => $platform->name,
                'status' => $platformStatus,
            ]);
        }

        $this->post->status = $allSuccessful ? 'published' : 'scheduled';
        $this->post->save();

        Log::info('Post publication completed', [
            'post_id' => $this->post->id,
            'status' => $this->post->status,
        ]);
    }

    protected function publishToPlatform(Post $post, $platform): string
    {
        switch ($platform->id) {
            case 1:
                // Platform 1 always succeeds
                return 'published';
            case 2:
                // Platform 2: Fails if content exceeds 280 characters
                return strlen($post->content) <= 280 ? 'published' : 'failed';
            case 3:
                // Platform 3: 10% chance of failure
                return mt_rand(1, 100) <= 90 ? 'published' : 'failed';
            default:
                return 'failed';
        }
    }
}
