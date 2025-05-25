<?php

namespace Tests\Feature;

use App\Models\Platform;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private Platform $platform;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('auth_token')->plainTextToken;
        $this->platform = Platform::factory()->create([
            'name' => 'Twitter',
            'type' => 'social'
        ]);
    }

    public function test_user_can_view_platforms()
    {
        Platform::factory()->create(['name' => 'Facebook', 'type' => 'social']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/platforms');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'active',
                    ],
                ],
            ]);
    }

    public function test_user_can_toggle_platform_on()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/platforms/toggle', [
                'platform_id' => $this->platform->id,
                'active' => true,
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'status' => Response::HTTP_OK,
                'message' => 'Platform toggled successfully',
                'data' => [
                    'platform_id' => $this->platform->id,
                    'active' => true,
                ],
            ]);

        $this->assertDatabaseHas('platform_user', [
            'user_id' => $this->user->id,
            'platform_id' => $this->platform->id,
        ]);
    }

    public function test_user_can_toggle_platform_off()
    {
        // First attach the platform
        $this->user->platforms()->attach($this->platform->id);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/platforms/toggle', [
                'platform_id' => $this->platform->id,
                'active' => false,
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'status' => Response::HTTP_OK,
                'message' => 'Platform toggled successfully',
                'data' => [
                    'platform_id' => $this->platform->id,
                    'active' => false,
                ],
            ]);

        $this->assertDatabaseMissing('platform_user', [
            'user_id' => $this->user->id,
            'platform_id' => $this->platform->id,
        ]);
    }

    public function test_unauthorized_user_cannot_access_platforms()
    {
        $response = $this->getJson('/api/platforms');
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_user_cannot_toggle_invalid_platform()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/platforms/toggle', [
                'platform_id' => 999,
                'active' => true,
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
    public function test_toggle_platform_invalidates_cache()
    {
        $platform = Platform::factory()->create(['name' => 'Twitter']);
        Cache::put("user_platforms_{$this->user->id}", [], now()->addHours(1));

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/platforms/toggle', [
                'platform_id' => $platform->id,
                'active' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 200,
                'message' => 'Platform toggled successfully',
                'data' => [
                    'platform_id' => $platform->id,
                    'active' => true,
                ],
            ]);

        $this->assertNull(Cache::get("user_platforms_{$this->user->id}"), 'Cache should be invalidated');
        $this->assertDatabaseHas('platform_user', [
            'user_id' => $this->user->id,
            'platform_id' => $platform->id,
        ]);

        // Verify index reflects updated state
        $indexResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/platforms');
        $indexResponse->assertJsonFragment([
            'id' => $platform->id,
            'name' => 'Twitter',
            'active' => true,
        ]);
    }
}
