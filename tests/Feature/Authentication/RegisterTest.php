<?php

namespace Tests\Feature\Authentication;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_register_success(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'username' => 'userregister',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '085868525150'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => true,
                'code' => 201,
                'message' => 'User Register Successfully',
                'data' => [
                    'user' => [
                        'id' => true,
                        'username' => true,
                        'phone' => true,
                    ],
                    'access_token' => true,
                ]
            ]);

        $this->assertArrayHasKey('access_token', $response->json('data'));
    }

    public function test_register_failed_username_already_taken()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/register', [
            'username' => $user->username,
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '085868525150'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'code' => 422,
                'message' => 'The given data was invalid.',
                'data' => [],
                'errors' => [
                    'username' => ['The username has already been taken.'],
                ]
            ]);
    }

    public function test_register_failed_phone_already_taken()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/register', [
            'username' => 'userbaru',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => $user->phone
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'code' => 422,
                'message' => 'The given data was invalid.',
                'data' => [],
                'errors' => [
                    'phone' => ['The phone has already been taken.'],
                ]
            ]);
    }
}
