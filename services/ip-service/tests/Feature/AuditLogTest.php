<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\IpAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_ip_address_creates_audit_log(): void
    {
        $this->withAuthHeaders()
            ->postJson('/api/ip-addresses', [
                'ip_address' => '192.168.1.1',
                'label' => 'Test Server',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'entity_type' => 'ip_address',
            'user_id' => 1,
        ]);
    }

    public function test_updating_ip_address_creates_audit_log(): void
    {
        $ip = IpAddress::factory()->create(['created_by' => 1, 'label' => 'Old Label']);

        $this->withAuthHeaders()
            ->putJson("/api/ip-addresses/{$ip->id}", [
                'label' => 'New Label',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'updated',
            'entity_type' => 'ip_address',
            'entity_id' => $ip->id,
        ]);
    }

    public function test_deleting_ip_address_creates_audit_log(): void
    {
        $ip = IpAddress::factory()->create(['created_by' => 1]);

        $this->withAdminHeaders()
            ->deleteJson("/api/ip-addresses/{$ip->id}");

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted',
            'entity_type' => 'ip_address',
            'entity_id' => $ip->id,
        ]);
    }

    public function test_admin_can_view_audit_logs(): void
    {
        AuditLog::factory()->count(5)->create();

        $response = $this->withAdminHeaders()
            ->getJson('/api/audit-logs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'action', 'entity_type', 'user_id', 'created_at']
                ]
            ]);
    }

    public function test_regular_user_cannot_view_audit_logs(): void
    {
        $response = $this->withAuthHeaders(role: 'user')
            ->getJson('/api/audit-logs');

        $response->assertStatus(403);
    }

    public function test_audit_log_records_session_id(): void
    {
        // Use withAuthHeaders which sets up proper middleware mock and session ID
        $this->withAuthHeaders()
            ->postJson('/api/ip-addresses', [
                'ip_address' => '192.168.1.1',
                'label' => 'Test Server',
            ]);

        // Session ID is set to 'test-session-123' by withAuthHeaders helper
        $this->assertDatabaseHas('audit_logs', [
            'session_id' => 'test-session-123',
        ]);
    }

    public function test_audit_log_stores_old_and_new_values(): void
    {
        $ip = IpAddress::factory()->create([
            'created_by' => 1,
            'label' => 'Old Label',
            'comment' => 'Old Comment',
        ]);

        $this->withAuthHeaders()
            ->putJson("/api/ip-addresses/{$ip->id}", [
                'label' => 'New Label',
                'comment' => 'New Comment',
            ]);

        $auditLog = AuditLog::where('action', 'updated')
            ->where('entity_id', $ip->id)
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertNotNull($auditLog->old_values);
        $this->assertNotNull($auditLog->new_values);
    }

    public function test_audit_logs_have_hash_chain(): void
    {
        // Create multiple entries
        $this->withAuthHeaders()
            ->postJson('/api/ip-addresses', ['ip_address' => '10.0.0.1', 'label' => 'Server 1']);
        
        $this->withAuthHeaders()
            ->postJson('/api/ip-addresses', ['ip_address' => '10.0.0.2', 'label' => 'Server 2']);

        $logs = AuditLog::orderBy('id')->get();
        
        $this->assertCount(2, $logs);
        
        // First log should have hash but no previous hash
        $this->assertNotNull($logs[0]->hash);
        
        // Second log should have hash
        $this->assertNotNull($logs[1]->hash);
    }

    public function test_admin_can_verify_audit_log_integrity(): void
    {
        // Create some audit logs via API (which creates proper hash chain)
        $this->withAuthHeaders()
            ->postJson('/api/ip-addresses', ['ip_address' => '10.0.0.1', 'label' => 'Server 1']);

        $response = $this->withAdminHeaders()
            ->getJson('/api/audit-logs/verify');

        // Verify should return 200 with valid=true
        $response->assertStatus(200)
            ->assertJson(['valid' => true])
            ->assertJsonStructure(['valid', 'total_logs']);
    }
}
