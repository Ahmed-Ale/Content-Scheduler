<?php

namespace Tests\Feature;

use App\Models\Platform;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    public function test_it_toggles_a_platform_on_for_a_user()
    {
        $user = User::factory()->create();
        $platform = Platform::factory()->create(['name' => 'Twitter', 'type' => 'twitter']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/platforms/toggle', [
                'platform_id' => $platform->id,
                'active' => true,
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'status' => Response::HTTP_OK,
                'message' => 'Platform toggled successfully',
                'data' => [
                    'platform_id' => $platform->id,
                    'active' => true,
                ],
            ]);

        $this->assertDatabaseHas('platform_user', [
            'user_id' => $user->id,
            'platform_id' => $platform->id,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);
    }

    public function test_it_toggles_a_platform_off_for_a_user()
    {
        $user = User::factory()->create();
        $platform = Platform::factory()->create(['name' => 'Twitter', 'type' => 'twitter']);
        $user->platforms()->attach($platform->id);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/platforms/toggle', [
                'platform_id' => $platform->id,
                'active' => false,
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'status' => Response::HTTP_OK,
                'message' => 'Platform toggled successfully',
                'data' => [
                    'platform_id' => $platform->id,
                    'active' => false,
                ],
            ]);

        $this->assertDatabaseMissing('platform_user', [
            'user_id' => $user->id,
            'platform_id' => $platform->id,
        ]);
    }

    public function test_it_validates_platform_toggle_request()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/platforms/toggle', [
                'platform_id' => 999,
                'active' => 'invalid',
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['platform_id', 'active']);
    }
}
