<?php

namespace Tests\Feature;

use App\Models\HostMetric;
use App\Models\MonitoredHost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LatestHostMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_later_inserted_historical_metric_is_not_considered_the_latest(): void
    {
        $host = $this->host('Servidor A', '192.0.2.10');

        $this->metric($host, '2026-11-24 10:00:00', 10);
        $recent = $this->metric($host, '2026-11-24 12:00:00', 20);
        $historical = $this->metric($host, '2026-11-23 08:00:00', 99);

        $this->assertGreaterThan($recent->id, $historical->id);

        $latest = HostMetric::query()->latestPerHost()->sole();

        $this->assertSame($recent->id, $latest->id);
        $this->assertSame(20.0, (float) $latest->cpu_usage);
    }

    public function test_equal_timestamps_use_the_greatest_id_as_tiebreaker(): void
    {
        $host = $this->host('Servidor A', '192.0.2.10');

        $this->metric($host, '2026-11-24 12:00:00', 25);
        $second = $this->metric($host, '2026-11-24 12:00:00', 35);
        $this->metric($host, '2026-11-24 09:00:00', 90);

        $latest = HostMetric::query()->latestPerHost()->sole();

        $this->assertSame($second->id, $latest->id);
        $this->assertSame(35.0, (float) $latest->cpu_usage);
    }

    public function test_returns_exactly_one_latest_metric_per_host(): void
    {
        $hostA = $this->host('Servidor A', '192.0.2.10');
        $hostB = $this->host('Servidor B', '192.0.2.11');
        $this->host('Sin métricas', '192.0.2.12');

        $this->metric($hostA, '2026-11-24 09:00:00', 10);
        $latestA = $this->metric($hostA, '2026-11-24 12:00:00', 20);
        $this->metric($hostB, '2026-11-24 10:00:00', 50);
        $latestB = $this->metric($hostB, '2026-11-24 11:00:00', 60);
        $this->metric($hostB, '2026-11-23 09:00:00', 99);

        $metrics = HostMetric::query()->latestPerHost()->get();

        $this->assertCount(2, $metrics);
        $this->assertEqualsCanonicalizing(
            [$latestA->id, $latestB->id],
            $metrics->modelKeys()
        );
    }

    public function test_ranking_uses_the_latest_cpu_value_not_a_historical_peak(): void
    {
        $hostA = $this->host('Servidor A', '192.0.2.10');
        $hostB = $this->host('Servidor B', '192.0.2.11');

        $this->metric($hostA, '2026-11-24 12:00:00', 25);
        $this->metric($hostA, '2026-11-23 12:00:00', 98);
        $this->metric($hostB, '2026-11-24 11:00:00', 80);

        $ranking = HostMetric::query()
            ->latestPerHost()
            ->orderByDesc('cpu_usage')
            ->pluck('monitored_host_id')
            ->all();

        $this->assertSame([$hostB->id, $hostA->id], $ranking);
    }

    private function host(string $name, string $ip): MonitoredHost
    {
        return MonitoredHost::create([
            'name' => $name,
            'hostname' => str_replace(' ', '-', strtolower($name)),
            'ip_address' => $ip,
            'host_type' => 'server',
            'status' => 'unknown',
        ]);
    }

    private function metric(MonitoredHost $host, string $time, float $cpu): HostMetric
    {
        return HostMetric::create([
            'monitored_host_id' => $host->id,
            'cpu_usage' => $cpu,
            'ram_usage' => 40,
            'disk_usage' => 60,
            'uptime_seconds' => 1000,
            'recorded_at' => $time,
        ]);
    }
}
