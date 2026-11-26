<?php

namespace Tests\Feature;

use App\Events\DashboardUpdated;
use Illuminate\Broadcasting\Channel;
use Tests\TestCase;

class DashboardUpdatedPayloadTest extends TestCase
{
    public function test_event_exposes_expected_channel_name_and_payload(): void
    {
        $event = new DashboardUpdated(
            type: 'host_metric_created',
            message: 'Lectura recibida',
            metricId: 42
        );

        $this->assertInstanceOf(Channel::class, $event->broadcastOn());
        $this->assertSame('infrawatch.dashboard', $event->broadcastOn()->name);
        $this->assertSame('dashboard.updated', $event->broadcastAs());
        $this->assertSame('broadcasts', $event->broadcastQueue());
        $this->assertSame('database', $event->connection);
        $this->assertSame([
            'type' => 'host_metric_created',
            'message' => 'Lectura recibida',
            'metric_id' => 42,
            'timestamp' => $event->occurredAt,
        ], $event->broadcastWith());
    }
}
