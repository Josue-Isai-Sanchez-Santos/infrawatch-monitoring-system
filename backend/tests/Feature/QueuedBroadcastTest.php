<?php

namespace Tests\Feature;

use App\Events\DashboardUpdated;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueuedBroadcastTest extends TestCase
{
    public function test_dashboard_event_uses_a_persistent_queue(): void
    {
        Queue::fake();

        // Simular una configuración global síncrona.
        config([
            'queue.default' => 'sync',
            'broadcasting.default' => 'reverb',
        ]);

        $event = new DashboardUpdated(
            type: 'host_metric_created',
            message: 'Test',
            metricId: 123
        );

        $this->assertInstanceOf(
            ShouldBroadcast::class,
            $event
        );

        $this->assertInstanceOf(
            ShouldDispatchAfterCommit::class,
            $event
        );

        $this->assertInstanceOf(
            ShouldRescue::class,
            $event
        );

        $this->assertSame(
            'database',
            $event->connection
        );

        $this->assertSame(
            'broadcasts',
            $event->broadcastQueue()
        );

        DashboardUpdated::dispatch(
            type: 'host_metric_created',
            message: 'Test',
            metricId: 123
        );

        Queue::assertPushedOn(
            'broadcasts',
            BroadcastEvent::class
        );
    }

    public function test_broadcast_is_queued_after_transaction_commit(): void
    {
        Queue::fake();

        DB::transaction(function (): void {
            DashboardUpdated::dispatch(
                type: 'host_metric_created',
                message: 'Commit test',
                metricId: 456
            );

            // El evento todavía no debe encolarse.
            Queue::assertNotPushed(
                BroadcastEvent::class
            );
        });

        // Después del commit puede encolarse.
        Queue::assertPushedOn(
            'broadcasts',
            BroadcastEvent::class
        );
    }
}
