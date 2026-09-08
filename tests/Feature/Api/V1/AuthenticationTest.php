<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    /**
     * Test user login with valid credentials
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'refresh_token',
                ],
            ]);
    }

    /**
     * Test login fails with invalid credentials
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test user can refresh token
     */
    public function test_user_can_refresh_token(): void
    {
        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $this->adminToken,
        ]);

        // Should return new token
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                ],
            ]);
    }

    /**
     * Test authenticated user can get own profile
     */
    public function test_authenticated_user_can_get_profile(): void
    {
        $response = $this->withHeaders($this->getApiHeaders($this->adminToken))
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ],
            ]);
    }

    /**
     * Test user can logout
     */
    public function test_user_can_logout(): void
    {
        $response = $this->withHeaders($this->getApiHeaders($this->adminToken))
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test unauthenticated request fails
     */
    public function test_unauthenticated_request_fails(): void
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(401);
    }
}
