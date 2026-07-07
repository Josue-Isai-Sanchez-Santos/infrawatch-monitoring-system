<?php

namespace App\Filament\Widgets;

use App\Models\Alert;
use App\Models\MonitoredHost;
use App\Models\MonitoredService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InfrastructureStatusOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalHosts = MonitoredHost::count();
        $onlineHosts = MonitoredHost::where('status', 'online')->count();
        $offlineHosts = MonitoredHost::where('status', 'offline')->count();

        $totalServices = MonitoredService::count();
        $onlineServices = MonitoredService::where('status', 'online')->count();
        $offlineServices = MonitoredService::where('status', 'offline')->count();

        $openAlerts = Alert::where('status', 'open')->count();
        $criticalAlerts = Alert::where('status', 'open')
            ->where('severity', 'critical')
            ->count();

        $healthPercentage = $totalServices > 0
            ? round(($onlineServices / $totalServices) * 100, 1)
            : 0;

        $healthColor = match (true) {
            $healthPercentage >= 90 && $criticalAlerts === 0 => 'success',
            $healthPercentage >= 70 => 'warning',
            default => 'danger',
        };

        return [
            Stat::make('Salud general', "{$healthPercentage}%")
                ->description("{$onlineServices} de {$totalServices} servicios disponibles")
                ->color($healthColor)
                ->icon('heroicon-o-heart'),

            Stat::make('Equipos monitoreados', $totalHosts)
                ->description("Online: {$onlineHosts} / Offline: {$offlineHosts}")
                ->color($offlineHosts > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-server-stack'),

            Stat::make('Servicios monitoreados', $totalServices)
                ->description("Online: {$onlineServices} / Offline: {$offlineServices}")
                ->color($offlineServices > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-signal'),

            Stat::make('Alertas abiertas', $openAlerts)
                ->description("Críticas abiertas: {$criticalAlerts}")
                ->color($criticalAlerts > 0 ? 'danger' : ($openAlerts > 0 ? 'warning' : 'success'))
                ->icon('heroicon-o-bell-alert'),
        ];
    }
}
