<?php

namespace Tests\Feature;

use App\Events\DashboardUpdated;
use App\Models\Alert;
use App\Models\MonitoredHost;
use App\Models\MonitoredService;
use App\Models\User;
use App\Services\AlertResolutionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ManualAlertResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_technician_can_resolve_an_alert_without_affecting_other_alerts(): void
    {
        Event::fake([DashboardUpdated::class]);
        $technician = User::factory()->create(['role' => 'technician']);
        $first = $this->makeAlert();
        $second = $this->makeAlert();

        $this->assertTrue(app(AlertResolutionService::class)->resolve($technician, $first));

        $this->assertDatabaseHas('alerts', [
            'id' => $first->id,
            'status' => 'resolved',
        ]);
        $this->assertNotNull($first->fresh()->resolved_at);
        $this->assertDatabaseHas('alerts', [
            'id' => $second->id,
            'status' => 'open',
            'resolved_at' => null,
        ]);
    }

    public function test_observer_cannot_resolve_an_alert(): void
    {
        Event::fake([DashboardUpdated::class]);
        $observer = User::factory()->create(['role' => 'observer']);
        $alert = $this->makeAlert();

        try {
            app(AlertResolutionService::class)->resolve($observer, $alert);
            $this->fail('Observer should not be allowed to resolve alerts.');
        } catch (AuthorizationException) {
            $this->assertDatabaseHas('alerts', [
                'id' => $alert->id,
                'status' => 'open',
                'resolved_at' => null,
            ]);
        }
    }

    public function test_resolving_an_already_resolved_alert_is_idempotent(): void
    {
        Event::fake([DashboardUpdated::class]);
        $technician = User::factory()->create(['role' => 'technician']);
        $alert = $this->makeAlert();
        $service = app(AlertResolutionService::class);

        $this->assertTrue($service->resolve($technician, $alert));
        $resolvedAt = $alert->fresh()->resolved_at->toDateTimeString();

        $this->assertFalse($service->resolve($technician, $alert));
        $this->assertSame($resolvedAt, $alert->fresh()->resolved_at->toDateTimeString());
    }

    private function makeAlert(): Alert
    {
        $host = MonitoredHost::create([
            'name' => 'Host alerta',
            'hostname' => 'alert-test-host',
            'ip_address' => '192.0.2.'.(10 + MonitoredHost::count()),
            'host_type' => 'server',
            'status' => 'online',
        ]);

        $monitoredService = MonitoredService::create([
            'monitored_host_id' => $host->id,
            'name' => 'Servicio alerta',
            'port' => 8000 + MonitoredHost::count(),
            'protocol' => 'tcp',
            'status' => 'offline',
        ]);

        return Alert::create([
            'monitored_host_id' => $host->id,
            'monitored_service_id' => $monitoredService->id,
            'type' => 'service_down',
            'severity' => 'critical',
            'title' => 'Alerta de prueba',
            'message' => 'Falla simulada',
            'status' => 'open',
            'triggered_at' => now(),
        ]);
    }
}
