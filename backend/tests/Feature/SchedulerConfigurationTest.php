<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\CacheEventMutex;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

        $this->assertSame(
            'database',
            $event->mutex->store
        );
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

        $this->assertSame(
            '0 3 * * *',
            $event->expression
        );

        $this->assertTrue($event->withoutOverlapping);

        $this->assertSame(
            1440,
            $event->expiresAt
        );

        $this->assertSame(
            'database',
            $event->mutex->store
        );
    }

    public function test_database_mutex_persists_and_releases_lock(): void
    {
        $event = $this->findScheduledEvent('monitor:services');

        $mutex = $event->mutex;

        $this->assertInstanceOf(
            CacheEventMutex::class,
            $mutex
        );

        $this->assertSame(
            'database',
            $mutex->store
        );

        // Obtener exactamente el prefijo utilizado
        // por el almacenamiento database de Laravel.
        $prefix = Cache::store('database')
            ->getStore()
            ->getPrefix();

        $lockKey = $prefix.$event->mutexName();

        // Comprobar directamente la tabla de bloqueos.
        // No utilizamos $mutex->exists(), porque puede
        // intentar adquirir un bloqueo internamente.
        $this->assertDatabaseMissing('cache_locks', [
            'key' => $lockKey,
        ]);

        // Simular el inicio de una tarea programada.
        $this->assertTrue(
            $mutex->create($event)
        );

        try {
            // Verificar que el bloqueo se almacenó
            // correctamente en la base de datos.
            $this->assertDatabaseHas('cache_locks', [
                'key' => $lockKey,
            ]);
        } finally {
            // Liberarlo aunque falle una aserción.
            $mutex->forget($event);
        }

        // Confirmar que el bloqueo fue eliminado.
        $this->assertDatabaseMissing('cache_locks', [
            'key' => $lockKey,
        ]);
    }

    private function findScheduledEvent(string $command): Event
    {
        $event = collect(app(Schedule::class)->events())
            ->first(
                fn (Event $candidate): bool => str_contains(
                    (string) $candidate->command,
                    $command
                )
            );

        $this->assertInstanceOf(
            Event::class,
            $event
        );

        return $event;
    }
}
