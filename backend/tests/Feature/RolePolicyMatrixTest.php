<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\HostMetric;
use App\Models\MonitoredHost;
use App\Models\MonitoredService;
use App\Models\ServiceCheck;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RolePolicyMatrixTest extends TestCase
{
    public function test_observer_cannot_modify_infrastructure_alerts_or_history(): void
    {
        $observer = User::factory()->make(['role' => 'observer']);
        $gate = Gate::forUser($observer);

        foreach ([
            new MonitoredHost,
            new MonitoredService,
            new Alert,
            new HostMetric,
            new ServiceCheck,
        ] as $record) {
            $this->assertTrue($gate->allows('viewAny', $record::class));
            $this->assertTrue($gate->allows('view', $record));
            $this->assertFalse($gate->allows('create', $record::class));
            $this->assertFalse($gate->allows('update', $record));
            $this->assertFalse($gate->allows('delete', $record));
            $this->assertFalse($gate->allows('deleteAny', $record::class));
        }
    }

    public function test_technician_can_resolve_alert_but_cannot_delete_it(): void
    {
        $technician = User::factory()->make(['role' => 'technician']);
        $gate = Gate::forUser($technician);
        $alert = new Alert;

        $this->assertTrue($gate->allows('view', $alert));
        $this->assertTrue($gate->allows('update', $alert));
        $this->assertFalse($gate->allows('delete', $alert));
        $this->assertFalse($gate->allows('deleteAny', Alert::class));
        $this->assertFalse($gate->allows('create', Alert::class));
    }

    public function test_history_is_immutable_for_admin_as_well(): void
    {
        $admin = User::factory()->make(['role' => 'admin']);
        $gate = Gate::forUser($admin);

        foreach ([new HostMetric, new ServiceCheck] as $record) {
            $this->assertFalse($gate->allows('create', $record::class));
            $this->assertFalse($gate->allows('update', $record));
            $this->assertFalse($gate->allows('delete', $record));
            $this->assertFalse($gate->allows('deleteAny', $record::class));
        }
    }
}
