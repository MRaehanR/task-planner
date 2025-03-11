<?php

namespace Tests\Feature\Authentication;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;


class LoginTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * A basic feature test example.
     */
    public function test_login_success(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => $user->username,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => true,
                     'code' => 200,
                     'message' => 'User Login Successfully',
                     'data' => [
                         'user' => [
                             'id' => $user->id,
                             'username' => $user->username,
                             'phone' => $user->phone,
                         ],
                         'access_token' => true,
                     ]
                 ]);

        $this->assertArrayHasKey('access_token', $response->json('data'));
    }

    public function test_login_failed_account_not_found()
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'nouser',
            'password' => 'password123',
        ]);

        $response->assertStatus(404)
                 ->assertJson([
                     'status' => false,
                     'code' => 404,
                     'message' => 'Account not found',
                     'data' => []
                 ]);

        $this->assertArrayNotHasKey('access_token', $response->json('data'));
    }

    public function test_login_failed_password_wrong()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => $user->username,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'status' => false,
                     'code' => 401,
                     'message' => 'Email or Password does not match',
                     'data' => []
                 ]);

        $this->assertArrayNotHasKey('access_token', $response->json('data'));
    }
}
