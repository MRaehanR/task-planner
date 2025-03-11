<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_protected_route()
    {
        $response = $this->getJson('/api/test');

        $response->assertStatus(401)
            ->assertJson([
                'status' => false,
                'message' => 'Unauthenticated',
            ]);
    }

    public function test_authenticated_user_can_access_protected_route()
    {
        $user = User::factory()->create();

        $token = $user->createToken('access_token')->plainTextToken;

        $response = $this->getJson('/api/test', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'TEST',
            ]);
    }
}
