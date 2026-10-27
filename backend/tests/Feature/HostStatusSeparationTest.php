<?php

namespace Tests\Feature;

use App\Models\MonitoredHost;
use App\Models\MonitoredService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HostStatusSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tcp_monitor_does_not_change_agent_status_or_last_seen(): void
    {
        config([
            'services.telegram.enabled' => false,
        ]);

        $host = $this->createHost();

        $service = $this->createService(
            $host,
            'Servicio TCP de prueba',
            1,
            'tcp',
            'unknown',
            null
        );

        $this->artisan('monitor:services')
            ->assertSuccessful();

        $this->assertDatabaseHas('monitored_hosts', [
            'id' => $host->id,
            'status' => 'online',
            'last_seen_at' => '2026-10-01 10:00:00',
        ]);

        $this->assertSame(
            'offline',
            $service->fresh()->status
        );

        $this->assertSame(
            'critical',
            $host->fresh()->services_status
        );
    }

    public function test_one_offline_service_makes_aggregate_status_critical(): void
    {
        $host = $this->createHost();

        $http = $this->createService(
            $host,
            'HTTP',
            80,
            'tcp',
            'offline',
            now()->toDateTimeString()
        );

        $this->createService(
            $host,
            'PostgreSQL',
            5432,
            'tcp',
            'online',
            now()->toDateTimeString()
        );

        $this->assertSame(
            'critical',
            $host->services_status
        );

        // Al recuperarse HTTP, ambos servicios quedan online.
        $http->update([
            'status' => 'online',
        ]);

        $host->unsetRelation('services');

        $this->assertSame(
            'online',
            $host->services_status
        );

        // El agente mantiene su estado independiente.
        $this->assertSame(
            'online',
            $host->fresh()->status
        );
    }

    public function test_services_are_unknown_until_checked_and_udp_is_ignored(): void
    {
        $host = $this->createHost();

        $this->assertSame(
            'unknown',
            $host->services_status
        );

        $this->createService(
            $host,
            'Servicio UDP antiguo',
            5353,
            'udp',
            'offline',
            now()->toDateTimeString()
        );

        $host->unsetRelation('services');

        $this->assertSame(
            'unknown',
            $host->services_status
        );

        $http = $this->createService(
            $host,
            'HTTP',
            8080,
            'tcp',
            'online',
            null
        );

        $host->unsetRelation('services');

        $this->assertSame(
            'unknown',
            $host->services_status
        );

        $http->update([
            'last_checked_at' => now(),
        ]);

        $host->unsetRelation('services');

        $this->assertSame(
            'online',
            $host->services_status
        );
    }

    private function createHost(): MonitoredHost
    {
        return MonitoredHost::create([
            'name' => 'Servidor de prueba',
            'hostname' => 'status-test',
            'ip_address' => '127.0.0.1',
            'host_type' => 'server',
            'status' => 'online',
            'last_seen_at' => '2026-10-01 10:00:00',
        ]);
    }

    private function createService(
        MonitoredHost $host,
        string $name,
        int $port,
        string $protocol,
        string $status,
        ?string $lastCheckedAt
    ): MonitoredService {
        return MonitoredService::create([
            'monitored_host_id' => $host->id,
            'name' => $name,
            'port' => $port,
            'protocol' => $protocol,
            'status' => $status,
            'last_checked_at' => $lastCheckedAt,
        ]);
    }
}

