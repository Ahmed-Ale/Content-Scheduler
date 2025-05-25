<?php

namespace Tests\Feature;

use App\Models\Platform;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_user_posts()
    {
        $user = User::factory()->create();
        $posts = Post::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/posts');

        Log::info('Response:', ['response' => $response->getContent()]);
        $response->assertStatus(200)
            ->assertJsonCount(3)
            ->assertJsonFragment(['title' => $posts[0]->title]);
    }
    //    protected function setUp(): void
    //    {
    //        parent::setUp();
    //        Storage::fake('public');
    //    }
    //
    //    public function test_it_creates_a_post_with_platforms_and_image()
    //    {
    //        // Arrange
    //        $user = User::factory()->create();
    //        $platforms = Platform::factory()->count(2)->create();
    //        $image = UploadedFile::fake()->image('post.jpg');
    //
    //        // Act
    //        $response = $this->actingAs($user, 'sanctum')->postJson('/api/posts', [
    //            'title' => 'Test Post',
    //            'content' => 'This is a test post.',
    //            'scheduled_time' => now()->addHour()->toDateTimeString(),
    //            'platforms' => $platforms->pluck('id')->toArray(),
    //            'image' => $image,
    //        ]);
    //
    //        // Assert
    //        $response->assertStatus(201)
    //            ->assertJson(['message' => 'Post scheduled']);
    //
    //        $this->assertDatabaseHas('posts', [
    //            'user_id' => $user->id,
    //            'title' => 'Test Post',
    //            'status' => 'scheduled',
    //        ]);
    //
    //        $post = Post::latest()->first();
    //        $this->assertCount(2, $post->platforms);
    //        $this->assertTrue(Storage::disk('public')->exists($post->image_url));
    //
    //        // Verify timestamps in post_platform
    //        foreach ($platforms as $platform) {
    //            $this->assertDatabaseHas('post_platform', [
    //                'post_id' => $post->id,
    //                'platform_id' => $platform->id,
    //                'platform_status' => 'pending',
    //                'created_at' => now()->toDateTimeString(),
    //                'updated_at' => now()->toDateTimeString(),
    //            ]);
    //        }
    //    }
    //
    //    public function test_it_enforces_rate_limiting_of_10_posts_per_day()
    //    {
    //        // Arrange
    //        $user = User::factory()->create();
    //        Post::factory()->count(10)->create([
    //            'user_id' => $user->id,
    //            'created_at' => today(),
    //        ]);
    //
    //        // Act
    //        $response = $this->actingAs($user, 'sanctum')->postJson('/api/posts', [
    //            'title' => 'Excess Post',
    //            'content' => 'This should fail.',
    //            'scheduled_time' => now()->addHour()->toDateTimeString(),
    //            'platforms' => [Platform::factory()->create()->id],
    //        ]);
    //
    //        // Assert
    //        $response->assertStatus(429)
    //            ->assertJson(['message' => 'Daily post limit reached']);
    //    }
}
