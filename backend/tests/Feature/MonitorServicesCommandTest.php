<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\MonitoredHost;
use App\Models\MonitoredService;
use App\Models\ServiceCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorServicesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitor_services_command_creates_alert_when_service_fails(): void
    {
        config([
            'services.telegram.enabled' => false,
        ]);

        $host = MonitoredHost::create([
            'name' => 'Servidor de prueba',
            'hostname' => 'test-server',
            'ip_address' => '127.0.0.1',
            'operating_system' => 'Linux',
            'host_type' => 'server',
            'location' => 'Testing',
            'status' => 'online',
            'agent_token' => 'command-test-token',
        ]);

        $service = MonitoredService::create([
            'monitored_host_id' => $host->id,
            'name' => 'Servicio caído de prueba',
            'port' => 1,
            'protocol' => 'tcp',
            'status' => 'unknown',
        ]);

        $this->artisan('monitor:services')
            ->assertSuccessful();

        $this->assertDatabaseHas('service_checks', [
            'monitored_service_id' => $service->id,
            'status' => 'offline',
        ]);

        $this->assertDatabaseHas('alerts', [
            'monitored_host_id' => $host->id,
            'monitored_service_id' => $service->id,
            'type' => 'service_down',
            'severity' => 'critical',
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('monitored_services', [
            'id' => $service->id,
            'status' => 'offline',
        ]);

        $this->assertEquals(1, ServiceCheck::count());
        $this->assertEquals(1, Alert::count());
    }
}
