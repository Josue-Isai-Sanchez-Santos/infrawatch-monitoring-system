<?php

namespace Tests\Feature;

use App\Events\DashboardUpdated;
use App\Models\HostMetric;
use App\Models\MonitoredHost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgentMetricIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_reading_creates_one_metric(): void
    {
        Event::fake([DashboardUpdated::class]);

        $host = $this->createHost();
        $readingId = (string) Str::uuid();

        $this->withToken($host->agent_token)
            ->postJson(
                '/api/agent/metrics',
                $this->payload($readingId)
            )
            ->assertCreated()
            ->assertJsonPath('reading_id', $readingId);

        $this->assertDatabaseHas('host_metrics', [
            'monitored_host_id' => $host->id,
            'reading_id' => $readingId,
        ]);

        Event::assertDispatched(
            DashboardUpdated::class,
            1
        );
    }

    public function test_retry_does_not_duplicate_metric(): void
    {
        Event::fake([DashboardUpdated::class]);

        $host = $this->createHost();
        $readingId = (string) Str::uuid();

        $payload = $this->payload($readingId);

        $first = $this->withToken($host->agent_token)
            ->postJson('/api/agent/metrics', $payload)
            ->assertCreated();

        $second = $this->withToken($host->agent_token)
            ->postJson('/api/agent/metrics', $payload)
            ->assertOk();

        $this->assertSame(
            $first->json('metric_id'),
            $second->json('metric_id')
        );

        $this->assertSame(
            $readingId,
            $second->json('reading_id')
        );

        $this->assertSame(1, HostMetric::count());

        Event::assertDispatched(
            DashboardUpdated::class,
            1
        );
    }

    public function test_different_readings_create_different_metrics(): void
    {
        Event::fake([DashboardUpdated::class]);

        $host = $this->createHost();

        $this->withToken($host->agent_token)
            ->postJson(
                '/api/agent/metrics',
                $this->payload((string) Str::uuid())
            )
            ->assertCreated();

        $this->withToken($host->agent_token)
            ->postJson(
                '/api/agent/metrics',
                $this->payload((string) Str::uuid())
            )
            ->assertCreated();

        $this->assertSame(2, HostMetric::count());

        Event::assertDispatched(
            DashboardUpdated::class,
            2
        );
    }

    public function test_reading_ids_are_unique_per_host(): void
    {
        Event::fake([DashboardUpdated::class]);

        $firstHost = $this->createHost(
            '192.0.2.10'
        );

        $secondHost = $this->createHost(
            '192.0.2.11'
        );

        $readingId = (string) Str::uuid();

        $this->withToken($firstHost->agent_token)
            ->postJson(
                '/api/agent/metrics',
                $this->payload($readingId, '192.0.2.10')
            )
            ->assertCreated();

        $this->withToken($secondHost->agent_token)
            ->postJson(
                '/api/agent/metrics',
                $this->payload($readingId, '192.0.2.11')
            )
            ->assertCreated();

        $this->assertSame(2, HostMetric::count());
    }

    public function test_invalid_reading_id_is_rejected(): void
    {
        Event::fake([DashboardUpdated::class]);

        $host = $this->createHost();

        $this->withToken($host->agent_token)
            ->postJson(
                '/api/agent/metrics',
                $this->payload('invalid-uuid')
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reading_id']);

        $this->assertDatabaseCount('host_metrics', 0);

        Event::assertNotDispatched(
            DashboardUpdated::class
        );
    }

    public function test_legacy_agent_without_reading_id_still_works(): void
    {
        Event::fake([DashboardUpdated::class]);

        $host = $this->createHost();

        $payload = $this->payload(null);

        $this->withToken($host->agent_token)
            ->postJson('/api/agent/metrics', $payload)
            ->assertCreated();

        $this->assertDatabaseCount('host_metrics', 1);
    }

    private function createHost(
        string $ip = '192.0.2.10'
    ): MonitoredHost {
        return MonitoredHost::create([
            'name' => 'Servidor '.$ip,
            'hostname' => 'test-host',
            'ip_address' => $ip,
            'operating_system' => 'Linux',
            'host_type' => 'server',
            'status' => 'unknown',
        ]);
    }

    private function payload(
        ?string $readingId,
        string $ip = '192.0.2.10'
    ): array {
        $payload = [
            'hostname' => 'test-host',
            'ip_address' => $ip,
            'operating_system' => 'Linux',
            'cpu_usage' => 25.5,
            'ram_usage' => 40.0,
            'disk_usage' => 60.0,
            'uptime_seconds' => 1000,
        ];

        if ($readingId !== null) {
            $payload['reading_id'] = $readingId;
        }

        return $payload;
    }
}
