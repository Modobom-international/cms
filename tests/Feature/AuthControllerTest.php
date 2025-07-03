<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_successfully()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        $response = $this->postJson('/api/auth/register', $data);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'register_success',
            ]);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_register_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/auth/register', []);
        $response->assertStatus(422);
    }

    public function test_register_fails_with_duplicate_email()
    {
        User::factory()->create(['email' => 'test@example.com']);
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        $response = $this->postJson('/api/auth/register', $data);
        $response->assertStatus(422);
    }

    public function test_register_fails_with_password_mismatch()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword',
        ];
        $response = $this->postJson('/api/auth/register', $data);
        $response->assertStatus(422);
    }

    public function test_login_successfully()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
        $data = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];
        $response = $this->postJson('/api/auth/login', $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'login_success',
            ])
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                    'user'
                ]
            ]);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
        $data = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ];
        $response = $this->postJson('/api/auth/login', $data);
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'type' => 'invalid_credentials',
            ]);
    }

    public function test_login_fails_with_nonexistent_user()
    {
        $data = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ];
        $response = $this->postJson('/api/auth/login', $data);
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'type' => 'invalid_credentials',
            ]);
    }

    public function test_login_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/auth/login', []);
        $response->assertStatus(422);
    }

    public function test_logout_successfully()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $response = $this->postJson('/api/auth/logout');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'logout_success',
            ]);
    }

    public function test_logout_returns_401_when_not_authenticated()
    {
        $response = $this->postJson('/api/auth/logout');
        $response->assertStatus(401);
    }

    public function test_refresh_token_successfully()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $response = $this->postJson('/api/auth/refresh');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'token_refresh_success',
            ])
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in'
                ]
            ]);
    }

    public function test_refresh_returns_401_when_not_authenticated()
    {
        $response = $this->postJson('/api/auth/refresh');
        $response->assertStatus(401);
    }

    public function test_forgot_password_successfully()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $data = [
            'email' => 'test@example.com',
        ];
        $response = $this->postJson('/api/auth/forgot-password', $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'password_reset_link_sent',
            ]);
    }

    public function test_forgot_password_fails_with_nonexistent_email()
    {
        $data = [
            'email' => 'nonexistent@example.com',
        ];
        $response = $this->postJson('/api/auth/forgot-password', $data);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'type' => 'user_not_found',
            ]);
    }

    public function test_forgot_password_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/auth/forgot-password', []);
        $response->assertStatus(422);
    }

    public function test_reset_password_successfully()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $token = Password::createToken($user);
        $data = [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'token' => $token,
        ];
        $response = $this->postJson('/api/auth/reset-password', $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'password_reset_success',
            ]);
    }

    public function test_reset_password_fails_with_invalid_token()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $data = [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'token' => 'invalid_token',
        ];
        $response = $this->postJson('/api/auth/reset-password', $data);
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'type' => 'invalid_token',
            ]);
    }

    public function test_reset_password_fails_with_password_mismatch()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $token = Password::createToken($user);
        $data = [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
            'token' => $token,
        ];
        $response = $this->postJson('/api/auth/reset-password', $data);
        $response->assertStatus(422);
    }

    public function test_reset_password_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/auth/reset-password', []);
        $response->assertStatus(422);
    }

    public function test_register_with_additional_fields()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '1234567890',
            'address' => 'Test Address',
        ];
        $response = $this->postJson('/api/auth/register', $data);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'register_success',
            ]);
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'phone' => '1234567890',
        ]);
    }

    public function test_login_with_remember_me()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
        $data = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember_me' => true,
        ];
        $response = $this->postJson('/api/auth/login', $data);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'login_success',
            ]);
    }
} 