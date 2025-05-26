<?php

namespace Tests\Feature;

use App\Models\Platform;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    private Platform $platform;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('auth_token')->plainTextToken;
        $this->platform = Platform::factory()->create(['name' => 'Twitter', 'type' => 'social']);
    }

    public function test_user_can_create_post()
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->image('post.jpg');

        $this->user->platforms()->attach($this->platform->id);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/posts', [
                'title' => 'Test Post',
                'content' => 'Test content',
                'image' => $image,
                'scheduled_time' => now()->addDay()->format('Y-m-d H:i:s'),
                'platforms' => [$this->platform->id],
            ]);

        $response->assertStatus(Response::HTTP_CREATED);
        Storage::disk('public')->assertExists('images/'.$image->hashName());
    }

    public function test_user_cannot_exceed_daily_post_limit()
    {
        $scheduledDate = now()->addDay();
        Post::factory()->count(9)->create([
            'user_id' => $this->user->id,
            'scheduled_time' => $scheduledDate->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
        ]);

        // 10th post via API
        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/posts', [
                'title' => 'Test Post 10',
                'content' => 'Test content 10',
                'scheduled_time' => $scheduledDate->format('Y-m-d H:i:s'),
                'platforms' => [$this->platform->id],
            ]);

        // 11th post via API
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/posts', [
                'title' => 'Test Post',
                'content' => 'Test content',
                'scheduled_time' => $scheduledDate->format('Y-m-d H:i:s'),
                'platforms' => [$this->platform->id],
            ]);

        $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    }

    public function test_user_can_update_post()
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson("/api/posts/{$post->id}", [
                'title' => 'Updated Title',
                'content' => 'Updated content',
                'scheduled_time' => now()->addDays(2),
                'platforms' => [$this->platform->id],
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'status' => Response::HTTP_OK,
                'message' => 'Post updated successfully',
            ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ]);
    }

    public function test_user_can_delete_draft_post()
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson("/api/posts/{$post->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_user_cannot_delete_published_post()
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson("/api/posts/{$post->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_user_can_view_analytics()
    {
        Post::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);

        Post::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'scheduled',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/analytics');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'posts_per_platform',
                    'success_rate',
                    'scheduled_count',
                    'published_count',
                    'failed_count',
                ],
            ]);
    }

    public function test_user_can_export_analytics()
    {
        $platform = Platform::factory()->create(['name' => 'Twitter']);
        $this->user->platforms()->attach($platform->id);

        $posts = Post::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);
        foreach ($posts as $post) {
            $post->platforms()->attach($platform->id, ['platform_status' => 'published']);
        }

        // Use get() instead of getJson()
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->get('/api/analytics/export');

        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment; filename="analytics_export_', $contentDisposition);
        $this->assertStringContainsString('.csv"', $contentDisposition);

        $content = $response->getContent();
        $this->assertStringContainsString('Twitter,5,100,0,5,0', $content);
    }

    public function test_export_analytics_with_no_data()
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/analytics/export');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 200,
                'message' => 'No analytics data available for export',
            ]);
    }
}
