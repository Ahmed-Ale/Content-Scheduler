<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);
        $this->token = $this->user->createToken('auth_token')->plainTextToken;
    }

    public function test_user_can_view_profile()
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/user');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'status' => Response::HTTP_OK,
                'message' => 'Profile retrieved successfully',
                'data' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
            ]);
    }

    public function test_user_can_update_profile()
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/user', [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'status' => Response::HTTP_OK,
                'message' => 'Profile updated successfully',
                'data' => [
                    'name' => 'Jane Doe',
                    'email' => 'jane@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
    }

    public function test_user_can_update_password()
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/user', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'old_password' => 'password123',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertTrue(Hash::check('NewPassword123!', $this->user->fresh()->password));
    }

    public function test_user_cannot_update_password_with_incorrect_old_password()
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/user', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'old_password' => 'wrongpassword',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertTrue(Hash::check('password123', $this->user->fresh()->password));
    }

    public function test_user_can_delete_profile()
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/user');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'status' => Response::HTTP_OK,
                'message' => 'Profile deleted successfully',
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $this->user->id,
        ]);
    }
}
