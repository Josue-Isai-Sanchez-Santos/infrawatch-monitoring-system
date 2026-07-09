<?php

namespace Tests\Feature;

use App\Models\HostMetric;
use App\Models\MonitoredHost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentMetricsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_send_metrics_with_valid_token(): void
    {
        $host = MonitoredHost::create([
            'name' => 'Servidor de prueba',
            'hostname' => 'old-hostname',
            'ip_address' => '127.0.0.1',
            'operating_system' => 'Linux',
            'host_type' => 'server',
            'location' => 'Testing',
            'status' => 'offline',
            'agent_token' => 'test-agent-token',
        ]);

        $payload = [
            'hostname' => 'test-host',
            'ip_address' => '127.0.0.1',
            'operating_system' => 'Linux 6.x',
            'cpu_usage' => 25.5,
            'ram_usage' => 60.2,
            'disk_usage' => 70.1,
            'uptime_seconds' => 123456,
        ];

        $response = $this
            ->withHeader('Authorization', 'Bearer test-agent-token')
            ->postJson('/api/agent/metrics', $payload);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'message',
                'metric_id',
            ]);

        $this->assertDatabaseHas('host_metrics', [
            'monitored_host_id' => $host->id,
            'cpu_usage' => 25.5,
            'ram_usage' => 60.2,
            'disk_usage' => 70.1,
            'uptime_seconds' => 123456,
        ]);

        $this->assertDatabaseHas('monitored_hosts', [
            'id' => $host->id,
            'hostname' => 'test-host',
            'ip_address' => '127.0.0.1',
            'operating_system' => 'Linux 6.x',
            'status' => 'online',
        ]);

        $this->assertEquals(1, HostMetric::count());
    }

    public function test_agent_metrics_api_rejects_missing_token(): void
    {
        $payload = [
            'hostname' => 'test-host',
            'ip_address' => '127.0.0.1',
            'operating_system' => 'Linux 6.x',
            'cpu_usage' => 25.5,
            'ram_usage' => 60.2,
            'disk_usage' => 70.1,
            'uptime_seconds' => 123456,
        ];

        $response = $this->postJson('/api/agent/metrics', $payload);

        $response
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Missing agent token.',
            ]);
    }

    public function test_agent_metrics_api_rejects_invalid_token(): void
    {
        $payload = [
            'hostname' => 'test-host',
            'ip_address' => '127.0.0.1',
            'operating_system' => 'Linux 6.x',
            'cpu_usage' => 25.5,
            'ram_usage' => 60.2,
            'disk_usage' => 70.1,
            'uptime_seconds' => 123456,
        ];

        $response = $this
            ->withHeader('Authorization', 'Bearer invalid-token')
            ->postJson('/api/agent/metrics', $payload);

        $response
            ->assertForbidden()
            ->assertJson([
                'message' => 'Invalid agent token.',
            ]);
    }
}
