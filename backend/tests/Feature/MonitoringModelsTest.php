<?php

namespace Tests\Feature;

use App\Models\MonitoredHost;
use App\Models\MonitoredService;
use App\Models\ServiceCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_monitored_host(): void
    {
        $host = MonitoredHost::create([
            'name' => 'Servidor Local',
            'hostname' => 'local-server',
            'ip_address' => '127.0.0.1',
            'operating_system' => 'Linux',
            'host_type' => 'server',
            'location' => 'Laboratorio',
            'status' => 'offline',
            'agent_token' => 'host-token-123',
        ]);

        $this->assertDatabaseHas('monitored_hosts', [
            'id' => $host->id,
            'name' => 'Servidor Local',
            'ip_address' => '127.0.0.1',
            'status' => 'offline',
        ]);
    }

    public function test_can_create_service_check_for_monitored_service(): void
    {
        $host = MonitoredHost::create([
            'name' => 'Servidor Local',
            'hostname' => 'local-server',
            'ip_address' => '127.0.0.1',
            'operating_system' => 'Linux',
            'host_type' => 'server',
            'location' => 'Laboratorio',
            'status' => 'online',
            'agent_token' => 'host-token-456',
        ]);

        $service = MonitoredService::create([
            'monitored_host_id' => $host->id,
            'name' => 'Laravel Dev Server',
            'port' => 8000,
            'protocol' => 'tcp',
            'status' => 'unknown',
        ]);

        $check = ServiceCheck::create([
            'monitored_service_id' => $service->id,
            'status' => 'online',
            'response_time_ms' => 15,
            'message' => 'Service is reachable.',
            'checked_at' => now(),
        ]);

        $this->assertDatabaseHas('service_checks', [
            'id' => $check->id,
            'monitored_service_id' => $service->id,
            'status' => 'online',
            'response_time_ms' => 15,
        ]);
    }
}
