<?php

namespace Tests\Feature;

use App\Models\IpAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IpAddressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Helper to simulate authenticated request with user headers
     */
    protected function withAuthHeaders(int $userId = 1, string $role = 'user', string $email = 'test@example.com'): self
    {
        return $this->withHeaders([
            'X-User-Id' => $userId,
            'X-User-Role' => $role,
            'X-User-Email' => $email,
            'X-Session-Id' => 'test-session-123',
        ]);
    }

    public function test_user_can_list_all_ip_addresses(): void
    {
        IpAddress::factory()->count(5)->create();

        $response = $this->withAuthHeaders()
            ->getJson('/api/ip-addresses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'ip_address', 'label', 'comment', 'created_by', 'created_at', 'updated_at']
                ]
            ]);
    }

    public function test_user_can_create_ip_address(): void
    {
        $response = $this->withAuthHeaders(userId: 1)
            ->postJson('/api/ip-addresses', [
                'ip_address' => '192.168.1.100',
                'label' => 'Test Server',
                'comment' => 'A test comment',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'ip_address' => '192.168.1.100',
                'label' => 'Test Server',
                'comment' => 'A test comment',
            ]);

        $this->assertDatabaseHas('ip_addresses', [
            'ip_address' => '192.168.1.100',
            'label' => 'Test Server',
        ]);
    }

    public function test_user_can_create_ipv6_address(): void
    {
        $response = $this->withAuthHeaders()
            ->postJson('/api/ip-addresses', [
                'ip_address' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
                'label' => 'IPv6 Server',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'ip_address' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            ]);
    }

    public function test_create_ip_fails_with_invalid_ip(): void
    {
        $response = $this->withAuthHeaders()
            ->postJson('/api/ip-addresses', [
                'ip_address' => 'invalid-ip',
                'label' => 'Test Server',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ip_address']);
    }

    public function test_create_ip_fails_without_label(): void
    {
        $response = $this->withAuthHeaders()
            ->postJson('/api/ip-addresses', [
                'ip_address' => '192.168.1.100',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['label']);
    }

    public function test_user_can_view_single_ip_address(): void
    {
        $ip = IpAddress::factory()->create([
            'ip_address' => '10.0.0.1',
            'label' => 'Database Server',
        ]);

        $response = $this->withAuthHeaders()
            ->getJson("/api/ip-addresses/{$ip->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $ip->id,
                'ip_address' => '10.0.0.1',
                'label' => 'Database Server',
            ]);
    }

    public function test_user_can_update_own_ip_address(): void
    {
        $ip = IpAddress::factory()->create([
            'created_by' => 1,
            'label' => 'Old Label',
        ]);

        $response = $this->withAuthHeaders(userId: 1)
            ->putJson("/api/ip-addresses/{$ip->id}", [
                'label' => 'New Label',
                'comment' => 'Updated comment',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'label' => 'New Label',
                'comment' => 'Updated comment',
            ]);
    }

    public function test_user_cannot_update_others_ip_address(): void
    {
        $ip = IpAddress::factory()->create([
            'created_by' => 999, // Different user
            'label' => 'Original Label',
        ]);

        $response = $this->withAuthHeaders(userId: 1, role: 'user')
            ->putJson("/api/ip-addresses/{$ip->id}", [
                'label' => 'Hacked Label',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_any_ip_address(): void
    {
        $ip = IpAddress::factory()->create([
            'created_by' => 999,
            'label' => 'Original Label',
        ]);

        $response = $this->withAuthHeaders(userId: 1, role: 'admin')
            ->putJson("/api/ip-addresses/{$ip->id}", [
                'label' => 'Admin Updated',
            ]);

        $response->assertStatus(200)
            ->assertJson(['label' => 'Admin Updated']);
    }

    public function test_user_cannot_delete_ip_address(): void
    {
        $ip = IpAddress::factory()->create(['created_by' => 1]);

        $response = $this->withAuthHeaders(userId: 1, role: 'user')
            ->deleteJson("/api/ip-addresses/{$ip->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('ip_addresses', ['id' => $ip->id]);
    }

    public function test_admin_can_delete_any_ip_address(): void
    {
        $ip = IpAddress::factory()->create(['created_by' => 999]);

        $response = $this->withAuthHeaders(userId: 1, role: 'admin')
            ->deleteJson("/api/ip-addresses/{$ip->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('ip_addresses', ['id' => $ip->id]);
    }

    public function test_search_ip_addresses_by_ip(): void
    {
        IpAddress::factory()->create(['ip_address' => '192.168.1.1', 'label' => 'Server A']);
        IpAddress::factory()->create(['ip_address' => '10.0.0.1', 'label' => 'Server B']);

        $response = $this->withAuthHeaders()
            ->getJson('/api/ip-addresses?search=192.168');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_search_ip_addresses_by_label(): void
    {
        IpAddress::factory()->create(['ip_address' => '192.168.1.1', 'label' => 'Production Server']);
        IpAddress::factory()->create(['ip_address' => '10.0.0.1', 'label' => 'Test Server']);

        $response = $this->withAuthHeaders()
            ->getJson('/api/ip-addresses?search=Production');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }
}
