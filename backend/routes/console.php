<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('monitor:services')->everyMinute();

$retentionDays = (int) env('MONITORING_HISTORY_RETENTION_DAYS', 30);

Schedule::command("monitor:cleanup --days={$retentionDays} --force")
    ->dailyAt('03:00');
