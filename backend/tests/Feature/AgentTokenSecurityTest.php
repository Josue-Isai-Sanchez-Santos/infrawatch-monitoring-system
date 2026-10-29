<?php

namespace Tests\Feature;

use App\Models\MonitoredHost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentTokenSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_hosts_receive_unique_64_character_tokens(): void
    {
        $first = $this->createHost();

        $second = $this->createHost([
            'name' => 'Segundo servidor',
            'ip_address' => '192.0.2.11',
        ]);

        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9]{64}$/',
            $first->agent_token
        );

        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9]{64}$/',
            $second->agent_token
        );

        $this->assertNotSame(
            $first->agent_token,
            $second->agent_token
        );
    }

    public function test_agent_token_is_hidden_from_serialization(): void
    {
        $host = $this->createHost();

        $this->assertNotEmpty($host->agent_token);

        $this->assertArrayNotHasKey(
            'agent_token',
            $host->toArray()
        );

        $this->assertStringNotContainsString(
            $host->agent_token,
            $host->toJson()
        );
    }

    public function test_existing_explicit_tokens_are_preserved(): void
    {
        $host = $this->createHost([
            'agent_token' => 'legacy-agent-token',
        ]);

        $host->update([
            'name' => 'Servidor actualizado',
        ]);

        $this->assertSame(
            'legacy-agent-token',
            $host->fresh()->agent_token
        );
    }

    public function test_api_rejects_invalid_ip_addresses(): void
    {
        $host = $this->createHost();

        $payload = $this->validPayload();
        $payload['ip_address'] = 'not-an-ip-address';

        $this->withToken($host->agent_token)
            ->postJson('/api/agent/metrics', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ip_address']);

        $this->assertDatabaseCount('host_metrics', 0);

        $this->assertNull(
            $host->fresh()->last_seen_at
        );
    }

    public function test_api_accepts_valid_ipv6_addresses(): void
    {
        $host = $this->createHost();

        $payload = $this->validPayload();
        $payload['ip_address'] = '2001:db8::42';

        $this->withToken($host->agent_token)
            ->postJson('/api/agent/metrics', $payload)
            ->assertCreated();

        $this->assertDatabaseHas('monitored_hosts', [
            'id' => $host->id,
            'ip_address' => '2001:db8::42',
            'status' => 'online',
        ]);

        $this->assertDatabaseCount('host_metrics', 1);
    }

    public function test_api_limits_requests_per_agent_token(): void
    {
        $host = $this->createHost();
        $payload = $this->validPayload();

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->withToken($host->agent_token)
                ->postJson('/api/agent/metrics', $payload)
                ->assertCreated();
        }

        $this->withToken($host->agent_token)
            ->postJson('/api/agent/metrics', $payload)
            ->assertStatus(429);

        $this->assertDatabaseCount('host_metrics', 10);
    }

    public function test_ip_limit_rejects_rotating_invalid_tokens(): void
    {
        $payload = $this->validPayload();

        for ($attempt = 1; $attempt <= 120; $attempt++) {
            $this->withToken('invalid-token-'.$attempt)
                ->postJson('/api/agent/metrics', $payload)
                ->assertForbidden();
        }

        $this->withToken('invalid-token-121')
            ->postJson('/api/agent/metrics', $payload)
            ->assertStatus(429);

        $this->assertDatabaseCount('host_metrics', 0);
    }

    private function createHost(array $attributes = []): MonitoredHost
    {
        return MonitoredHost::create(array_merge([
            'name' => 'Servidor de seguridad',
            'hostname' => 'security-test',
            'ip_address' => '192.0.2.10',
            'operating_system' => 'Linux',
            'host_type' => 'server',
            'location' => 'Testing',
            'status' => 'unknown',
        ], $attributes));
    }

    private function validPayload(): array
    {
        return [
            'hostname' => 'security-test',
            'ip_address' => '192.0.2.10',
            'operating_system' => 'Linux',
            'cpu_usage' => 20.5,
            'ram_usage' => 35.0,
            'disk_usage' => 45.0,
            'uptime_seconds' => 3600,
        ];
    }
}
