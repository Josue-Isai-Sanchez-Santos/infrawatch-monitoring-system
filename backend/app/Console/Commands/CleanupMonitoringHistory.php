<?php

namespace App\Console\Commands;

use App\Models\HostMetric;
use App\Models\ServiceCheck;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CleanupMonitoringHistory extends Command
{
    protected $signature = 'monitor:cleanup
        {--days=30 : Número de días de historial que se conservarán}
        {--dry-run : Muestra cuántos registros se eliminarían sin borrarlos}
        {--force : Ejecuta la limpieza sin pedir confirmación}';

    protected $description = 'Delete old monitoring history records while preserving alerts';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if ($days <= 0) {
            $this->error('El número de días debe ser mayor a 0.');

            return self::FAILURE;
        }

        $cutoffDate = Carbon::now()->subDays($days);

        $this->info('Iniciando limpieza de historial de monitoreo...');
        $this->line("Conservando últimos {$days} días.");
        $this->line('Fecha límite: '.$cutoffDate->format('Y-m-d H:i:s'));

        $oldHostMetricsQuery = HostMetric::query()
            ->where('recorded_at', '<', $cutoffDate);

        $oldServiceChecksQuery = ServiceCheck::query()
            ->where('checked_at', '<', $cutoffDate);

        $oldHostMetricsCount = $oldHostMetricsQuery->count();
        $oldServiceChecksCount = $oldServiceChecksQuery->count();

        $this->newLine();

        $this->table(
            ['Tabla', 'Registros antiguos detectados'],
            [
                ['host_metrics', $oldHostMetricsCount],
                ['service_checks', $oldServiceChecksCount],
                ['alerts', 'No se eliminan'],
            ]
        );

        if ($dryRun) {
            $this->warn('Modo dry-run activo. No se eliminó ningún registro.');

            return self::SUCCESS;
        }

        if ($oldHostMetricsCount === 0 && $oldServiceChecksCount === 0) {
            $this->info('No hay registros antiguos para eliminar.');

            return self::SUCCESS;
        }

        if (! $force) {
            if (! $this->confirm('¿Deseas eliminar estos registros antiguos?', true)) {
                $this->warn('Limpieza cancelada.');

                return self::SUCCESS;
            }
        }

        $deletedHostMetrics = $oldHostMetricsQuery->delete();
        $deletedServiceChecks = $oldServiceChecksQuery->delete();

        $this->newLine();

        $this->info('Limpieza completada correctamente.');

        $this->table(
            ['Tabla', 'Registros eliminados'],
            [
                ['host_metrics', $deletedHostMetrics],
                ['service_checks', $deletedServiceChecks],
                ['alerts', '0'],
            ]
        );

        return self::SUCCESS;
    }
}
