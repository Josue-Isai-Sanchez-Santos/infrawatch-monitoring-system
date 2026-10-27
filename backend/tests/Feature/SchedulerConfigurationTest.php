<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\CacheEventMutex;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulerConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_runs_every_minute_without_overlapping(): void
    {
        $event = $this->findScheduledEvent('monitor:services');

        $this->assertSame('* * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertSame(10, $event->expiresAt);
        $this->assertInstanceOf(
            CacheEventMutex::class,
            $event->mutex
        );
        $this->assertSame('database', $event->mutex->store);
    }

    public function test_cleanup_runs_daily_with_configured_retention(): void
    {
        $event = $this->findScheduledEvent('monitor:cleanup');

        $days = config('monitoring.retention_days');

        $this->assertIsInt($days);
        $this->assertGreaterThan(0, $days);

        $this->assertStringContainsString(
            "--days={$days}",
            $event->command
        );

        $this->assertStringContainsString(
            '--force',
            $event->command
        );

        $this->assertSame('0 3 * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertSame(1440, $event->expiresAt);
        $this->assertSame('database', $event->mutex->store);
    }

    public function test_database_mutex_rejects_an_overlapping_execution(): void
    {
        $event = $this->findScheduledEvent('monitor:services');

        // Primera ejecución: obtiene el bloqueo.
        $this->assertTrue($event->mutex->create($event));

        try {
            // Segunda ejecución: no puede obtenerlo.
            $this->assertFalse(
                $event->mutex->create($event)
            );

            $this->assertTrue(
                $event->mutex->exists($event)
            );
        } finally {
            // Liberamos el bloqueo incluso si falla
            // alguna aserción.
            $event->mutex->forget($event);
        }

        $this->assertFalse(
            $event->mutex->exists($event)
        );
    }

    private function findScheduledEvent(string $command): Event
    {
        $event = collect(app(Schedule::class)->events())
            ->first(
                fn (Event $candidate): bool =>
                    str_contains(
                        (string) $candidate->command,
                        $command
                    )
            );

        $this->assertInstanceOf(Event::class, $event);

        return $event;
    }
}
