<?php

namespace Tests\Feature;

use App\Filament\Resources\HostMetricResource;
use App\Filament\Resources\ServiceCheckResource;
use App\Models\Alert;
use App\Models\HostMetric;
use App\Models\MonitoredHost;
use App\Models\MonitoredService;
use App\Models\ServiceCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class MonitoringHistoryReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_panel_roles_can_view_but_cannot_change_history(): void
    {
        foreach (['admin', 'technician', 'observer'] as $role) {
            $user = User::factory()->make(['role' => $role]);
            $gate = Gate::forUser($user);

            foreach ([new HostMetric, new ServiceCheck] as $record) {
                $modelClass = $record::class;

                $this->assertTrue($gate->allows('viewAny', $modelClass));
                $this->assertTrue($gate->allows('view', $record));

                $this->assertFalse($gate->allows('create', $modelClass));
                $this->assertFalse($gate->allows('update', $record));
                $this->assertFalse($gate->allows('delete', $record));
                $this->assertFalse($gate->allows('deleteAny', $modelClass));
                $this->assertFalse($gate->allows('restore', $record));
                $this->assertFalse($gate->allows('restoreAny', $modelClass));
                $this->assertFalse($gate->allows('forceDelete', $record));
                $this->assertFalse($gate->allows('forceDeleteAny', $modelClass));
                $this->assertFalse($gate->allows('replicate', $record));
            }
        }
    }

    public function test_unsupported_roles_cannot_view_monitoring_history(): void
    {
        $gate = Gate::forUser(User::factory()->make(['role' => 'guest']));

        foreach ([new HostMetric, new ServiceCheck] as $record) {
            $this->assertFalse($gate->allows('viewAny', $record::class));
            $this->assertFalse($gate->allows('view', $record));
        }
    }

    public function test_resources_only_register_the_list_page(): void
    {
        $this->assertSame(['index'], array_keys(HostMetricResource::getPages()));
        $this->assertSame(['index'], array_keys(ServiceCheckResource::getPages()));
    }

    public function test_dry_run_does_not_delete_any_history(): void
    {
        [$host, $service] = $this->makeHostAndService();
        $this->makeMetric($host, now()->subDays(50));
        $this->makeCheck($service, now()->subDays(50));

        $this->artisan('monitor:cleanup', [
            '--days' => 30,
            '--dry-run' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseCount('host_metrics', 1);
        $this->assertDatabaseCount('service_checks', 1);
    }

    public function test_cleanup_removes_old_history_but_keeps_recent_history_and_alerts(): void
    {
        [$host, $service] = $this->makeHostAndService();

        $oldMetric = $this->makeMetric($host, now()->subDays(50));
        $recentMetric = $this->makeMetric($host, now()->subDays(5));
        $oldCheck = $this->makeCheck($service, now()->subDays(50));
        $recentCheck = $this->makeCheck($service, now()->subDays(5));

        $alert = Alert::create([
            'monitored_host_id' => $host->id,
            'monitored_service_id' => $service->id,
            'type' => 'service_down',
            'severity' => 'critical',
            'title' => 'Prueba de historial',
            'message' => 'No borrar esta alerta',
            'status' => 'open',
            'triggered_at' => now()->subDays(50),
        ]);

        $this->artisan('monitor:cleanup', [
            '--days' => 30,
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseMissing('host_metrics', ['id' => $oldMetric->id]);
        $this->assertDatabaseHas('host_metrics', ['id' => $recentMetric->id]);
        $this->assertDatabaseMissing('service_checks', ['id' => $oldCheck->id]);
        $this->assertDatabaseHas('service_checks', ['id' => $recentCheck->id]);
        $this->assertDatabaseHas('alerts', ['id' => $alert->id]);
    }

    private function makeHostAndService(): array
    {
        $host = MonitoredHost::create([
            'name' => 'Servidor de pruebas',
            'hostname' => 'history-test',
            'ip_address' => '192.0.2.55',
            'host_type' => 'server',
            'status' => 'unknown',
        ]);

        $service = MonitoredService::create([
            'monitored_host_id' => $host->id,
            'name' => 'Servicio de pruebas',
            'port' => 9443,
            'protocol' => 'tcp',
            'status' => 'unknown',
        ]);

        return [$host, $service];
    }

    private function makeMetric(MonitoredHost $host, $at): HostMetric
    {
        return HostMetric::create([
            'monitored_host_id' => $host->id,
            'cpu_usage' => 12.5,
            'ram_usage' => 30.0,
            'disk_usage' => 40.0,
            'recorded_at' => $at,
        ]);
    }

    private function makeCheck(MonitoredService $service, $at): ServiceCheck
    {
        return ServiceCheck::create([
            'monitored_service_id' => $service->id,
            'status' => 'online',
            'response_time_ms' => 15,
            'checked_at' => $at,
        ]);
    }
}
