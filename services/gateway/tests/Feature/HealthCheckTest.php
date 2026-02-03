<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'services',
                'timestamp',
            ]);
    }

    public function test_health_endpoint_includes_service_statuses(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'services' => [
                    'auth' => ['status'],
                    'ip' => ['status'],
                ],
            ]);
    }
}
