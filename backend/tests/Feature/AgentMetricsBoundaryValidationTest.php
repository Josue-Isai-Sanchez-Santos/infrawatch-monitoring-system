<?php

namespace Tests\Feature;

use App\Events\DashboardUpdated;
use App\Models\MonitoredHost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AgentMetricsBoundaryValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_zero_and_one_hundred_percent_are_valid_metric_boundaries(): void
    {
        Event::fake([DashboardUpdated::class]);
        $host = $this->makeHost();

        $this->withToken($host->agent_token)->postJson('/api/agent/metrics', [
            ...$this->validPayload(),
            'cpu_usage' => 0,
            'ram_usage' => 100,
            'disk_usage' => 100,
            'uptime_seconds' => 0,
        ])->assertCreated();

        $this->assertDatabaseHas('host_metrics', [
            'monitored_host_id' => $host->id,
            'cpu_usage' => 0,
            'ram_usage' => 100,
            'disk_usage' => 100,
            'uptime_seconds' => 0,
        ]);
    }

    public function test_out_of_range_metric_values_cannot_write_partial_data(): void
    {
        Event::fake([DashboardUpdated::class]);
        $host = $this->makeHost();

        foreach ([
            ['cpu_usage', -0.01],
            ['cpu_usage', 100.01],
            ['ram_usage', -1],
            ['ram_usage', 101],
            ['disk_usage', -1],
            ['disk_usage', 101],
            ['uptime_seconds', -1],
            ['uptime_seconds', 1.5],
        ] as [$field, $value]) {
            $this->withToken($host->agent_token)->postJson('/api/agent/metrics', [
                ...$this->validPayload(),
                $field => $value,
            ])->assertUnprocessable()->assertJsonValidationErrors([$field]);
        }

        $this->assertDatabaseCount('host_metrics', 0);
        $this->assertNull($host->fresh()->last_seen_at);
        Event::assertNotDispatched(DashboardUpdated::class);
    }

    public function test_missing_required_metrics_are_rejected(): void
    {
        Event::fake([DashboardUpdated::class]);
        $host = $this->makeHost();
        $payload = $this->validPayload();
        unset($payload['cpu_usage']);

        $this->withToken($host->agent_token)
            ->postJson('/api/agent/metrics', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cpu_usage']);

        $this->assertDatabaseCount('host_metrics', 0);
    }

    private function makeHost(): MonitoredHost
    {
        return MonitoredHost::create([
            'name' => 'Servidor límites',
            'hostname' => 'limits-test',
            'ip_address' => '192.0.2.21',
            'host_type' => 'server',
            'status' => 'unknown',
        ]);
    }

    private function validPayload(): array
    {
        return [
            'hostname' => 'limits-test',
            'ip_address' => '192.0.2.21',
            'cpu_usage' => 25,
            'ram_usage' => 50,
            'disk_usage' => 75,
            'uptime_seconds' => 1000,
        ];
    }
}
