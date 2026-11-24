<?php

namespace Tests\Feature;

use App\Models\HostMetric;
use App\Models\MonitoredHost;
use App\Support\HostMetricChartData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class HostMetricChartDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_host_has_its_own_cpu_ram_and_disk_series(): void
    {
        $hostA = $this->host('Host A', '192.0.2.10');
        $hostB = $this->host('Host B', '192.0.2.11');

        $this->reading($hostA, '10:00:00', 10, 41, 35);
        $this->reading($hostB, '10:00:30', 90, 71, 65);
        $this->reading($hostA, '10:01:00', 20, 42, 36);
        $this->reading($hostB, '10:01:30', 80, 72, 66);

        foreach ([
            'cpu_usage' => [[10.0, 20.0], [90.0, 80.0]],
            'ram_usage' => [[41.0, 42.0], [71.0, 72.0]],
            'disk_usage' => [[35.0, 36.0], [65.0, 66.0]],
        ] as $field => [$valuesA, $valuesB]) {
            $this->assertSame($valuesA, HostMetricChartData::forHost($hostA->id, $field, 'Test')['datasets'][0]['data']);
            $this->assertSame($valuesB, HostMetricChartData::forHost((string) $hostB->id, $field, 'Test')['datasets'][0]['data']);
        }
    }

    public function test_reads_only_the_last_20_records_in_chronological_order(): void
    {
        $host = $this->host('Host A', '192.0.2.10');

        for ($index = 0; $index < 25; $index++) {
            $this->reading(
                $host,
                Carbon::parse('2026-11-24 10:00:00')->addMinutes($index)->format('H:i:s'),
                $index,
                30,
                40,
            );
        }

        $chart = HostMetricChartData::forHost($host->id, 'cpu_usage', 'CPU %');

        $this->assertCount(20, $chart['labels']);
        $this->assertSame(5.0, $chart['datasets'][0]['data'][0]);
        $this->assertSame(24.0, $chart['datasets'][0]['data'][19]);
        $this->assertSame('24/11 10:05:00', $chart['labels'][0]);
        $this->assertSame('24/11 10:24:00', $chart['labels'][19]);
    }

    public function test_no_selection_or_invalid_selection_returns_empty_data(): void
    {
        $host = $this->host('Host A', '192.0.2.10');
        $this->reading($host, '10:00:00', 50, 30, 40);

        foreach ([null, '', 'all', '-1', '1 OR 1=1', [], 999999] as $invalid) {
            $chart = HostMetricChartData::forHost($invalid, 'cpu_usage', 'CPU %');
            $this->assertSame([], $chart['labels']);
            $this->assertSame([], $chart['datasets'][0]['data']);
        }
    }

    public function test_missing_measurements_are_null_not_zero(): void
    {
        $host = $this->host('Host A', '192.0.2.10');

        $this->reading($host, '10:00:00', 12, 30, 40);
        $this->reading($host, '10:01:00', null, 30, 40);
        $this->reading($host, '10:02:00', 22, 30, 40);

        $chart = HostMetricChartData::forHost($host->id, 'cpu_usage', 'CPU %');

        $this->assertSame([12.0, null, 22.0], $chart['datasets'][0]['data']);
        $this->assertFalse($chart['datasets'][0]['spanGaps']);
    }

    public function test_same_timestamps_have_stable_order(): void
    {
        $host = $this->host('Host A', '192.0.2.10');
        $this->reading($host, '10:00:00', 10, 30, 40);
        $this->reading($host, '10:00:00', 20, 30, 40);

        $chart = HostMetricChartData::forHost($host->id, 'cpu_usage', 'CPU %');

        $this->assertSame([10.0, 20.0], $chart['datasets'][0]['data']);
    }

    public function test_query_cannot_select_an_unapproved_column(): void
    {
        $this->expectException(InvalidArgumentException::class);
        HostMetricChartData::forHost(1, 'agent_token', 'Unauthorized');
    }

    private function host(string $name, string $ip): MonitoredHost
    {
        return MonitoredHost::create([
            'name' => $name,
            'hostname' => strtolower(str_replace(' ', '-', $name)),
            'ip_address' => $ip,
            'host_type' => 'server',
            'status' => 'unknown',
        ]);
    }

    private function reading(
        MonitoredHost $host,
        string $time,
        ?float $cpu,
        ?float $ram,
        ?float $disk
    ): void {
        HostMetric::create([
            'monitored_host_id' => $host->id,
            'cpu_usage' => $cpu,
            'ram_usage' => $ram,
            'disk_usage' => $disk,
            'uptime_seconds' => 1000,
            'recorded_at' => Carbon::parse('2026-11-24 '.$time),
        ]);
    }
}
