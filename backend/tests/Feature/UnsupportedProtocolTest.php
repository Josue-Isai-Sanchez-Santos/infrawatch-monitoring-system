<?php

namespace Tests\Feature;

use App\Models\MonitoredHost;
use App\Models\MonitoredService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnsupportedProtocolTest extends TestCase
{
    use RefreshDatabase;

    public function test_udp_services_are_not_monitored_as_tcp(): void
    {
        config([
            'services.telegram.enabled' => false,
        ]);

        $host = MonitoredHost::create([
            'name' => 'Servidor UDP de prueba',
            'hostname' => 'udp-test-host',
            'ip_address' => '192.0.2.50',
            'operating_system' => 'Linux',
            'host_type' => 'server',
            'location' => 'Testing',
            'status' => 'unknown',
        ]);

        $service = MonitoredService::create([
            'monitored_host_id' => $host->id,
            'name' => 'Servicio UDP no soportado',
            'port' => 5353,
            'protocol' => 'udp',
            'status' => 'unknown',
        ]);

        $this->artisan('monitor:services')
            ->assertSuccessful();

        $this->assertDatabaseHas('monitored_services', [
            'id' => $service->id,
            'protocol' => 'udp',
            'status' => 'unknown',
            'last_checked_at' => null,
        ]);

        $this->assertDatabaseHas('monitored_hosts', [
            'id' => $host->id,
            'status' => 'unknown',
        ]);

        $this->assertDatabaseCount('service_checks', 0);
        $this->assertDatabaseCount('alerts', 0);
    }
}
