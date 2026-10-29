<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Comandos de consola
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Almacenamiento de bloqueos del Scheduler
|--------------------------------------------------------------------------
|
| Docker utiliza CACHE_STORE=array para la caché general.
| Sin embargo, los bloqueos de las tareas programadas
| deben compartirse entre procesos y contenedores.
|
| PostgreSQL almacena estos bloqueos mediante las
| tablas cache y cache_locks.
|
*/

Schedule::useCache('database');

/*
|--------------------------------------------------------------------------
| Monitoreo automático de servicios TCP
|--------------------------------------------------------------------------
|
| Se ejecuta cada minuto, siempre que la ejecución
| anterior haya terminado.
|
| El bloqueo caduca después de 10 minutos.
|
*/

Schedule::command('monitor:services')
    ->everyMinute()
    ->withoutOverlapping(10);

/*
|--------------------------------------------------------------------------
| Limpieza automática del historial de monitoreo
|--------------------------------------------------------------------------
|
| Se ejecuta diariamente a las 03:00, según la zona
| horaria configurada para el Scheduler.
|
| El número de días procede de config/monitoring.php.
| Se evita la ejecución simultánea de dos limpiezas.
|
*/

Schedule::command(
    'monitor:cleanup --days='.config('monitoring.retention_days').' --force'
)
    ->dailyAt('03:00')
    ->withoutOverlapping();
