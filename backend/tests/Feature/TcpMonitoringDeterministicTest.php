<?php

namespace Tests\Feature;

use App\Events\DashboardUpdated;
use App\Models\Alert;
use App\Models\MonitoredHost;
use App\Models\MonitoredService;
use App\Services\TcpServiceChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TcpMonitoringDeterministicTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_services_create_distinct_checks_and_recovery_resolves_only_the_failed_service(): void
    {
        config(['services.telegram.enabled' => false]);
        Event::fake([DashboardUpdated::class]);

        $host = MonitoredHost::create([
            'name' => 'Servidor multiservicio',
            'hostname' => 'multi-test',
            'ip_address' => '192.0.2.20',
            'host_type' => 'server',
            'status' => 'online',
            'last_seen_at' => '2026-10-01 10:00:00',
        ]);

        $http = MonitoredService::create([
            'monitored_host_id' => $host->id,
            'name' => 'HTTP',
            'port' => 8080,
            'protocol' => 'tcp',
            'status' => 'unknown',
        ]);

        $database = MonitoredService::create([
            'monitored_host_id' => $host->id,
            'name' => 'PostgreSQL',
            'port' => 5432,
            'protocol' => 'tcp',
            'status' => 'unknown',
        ]);

        $checker = $this->mock(TcpServiceChecker::class);
        $checker->shouldReceive('check')
            ->twice()
            ->with('192.0.2.20', 8080)
            ->andReturn([
                'reachable' => true,
                'response_time_ms' => 8,
                'error' => '',
            ]);
        $checker->shouldReceive('check')
            ->twice()
            ->with('192.0.2.20', 5432)
            ->andReturn(
                [
                    'reachable' => false,
                    'response_time_ms' => 3000,
                    'error' => 'connection refused',
                ],
                [
                    'reachable' => true,
                    'response_time_ms' => 12,
                    'error' => '',
                ]
            );

        $this->artisan('monitor:services')->assertSuccessful();

        $this->assertDatabaseHas('service_checks', [
            'monitored_service_id' => $http->id,
            'status' => 'online',
            'response_time_ms' => 8,
        ]);
        $this->assertDatabaseHas('service_checks', [
            'monitored_service_id' => $database->id,
            'status' => 'offline',
            'response_time_ms' => 3000,
        ]);
        $this->assertDatabaseCount('service_checks', 2);
        $this->assertDatabaseCount('alerts', 1);
        $this->assertSame('critical', $host->fresh()->services_status);

        $alert = Alert::query()->sole();
        $this->assertSame($database->id, $alert->monitored_service_id);
        $this->assertSame('open', $alert->status);

        $this->artisan('monitor:services')->assertSuccessful();

        $this->assertDatabaseCount('service_checks', 4);
        $this->assertDatabaseCount('alerts', 1);
        $this->assertSame('online', $host->fresh()->services_status);
        $this->assertSame('resolved', $alert->fresh()->status);
        $this->assertNotNull($alert->fresh()->resolved_at);
        $this->assertDatabaseHas('monitored_hosts', [
            'id' => $host->id,
            'status' => 'online',
            'last_seen_at' => '2026-10-01 10:00:00',
        ]);

        Event::assertDispatched(DashboardUpdated::class, 2);
    }
}
