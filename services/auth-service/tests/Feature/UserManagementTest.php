<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_all_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(3)->create(['role' => 'user']);

        Passport::actingAs($admin, ['admin']);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonCount(4); // 3 users + 1 admin
    }

    public function test_regular_user_cannot_list_users(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        Passport::actingAs($user, ['user']);

        $response = $this->getJson('/api/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Passport::actingAs($admin, ['admin']);

        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'role']]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'role' => 'user',
        ]);
    }

    public function test_regular_user_cannot_create_user(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        Passport::actingAs($user, ['user']);

        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_roles_are_correctly_assigned(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->assertEquals('admin', $admin->role);
        $this->assertEquals('user', $user->role);
    }
}
