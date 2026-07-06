<?php

namespace App\Filament\Widgets;

use App\Models\Alert;
use App\Models\MonitoredHost;
use App\Models\MonitoredService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InfraStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Equipos totales', MonitoredHost::count())
                ->description('Equipos registrados')
                ->icon('heroicon-o-server'),

            Stat::make('Equipos online', MonitoredHost::where('status', 'online')->count())
                ->description('Responden correctamente')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Equipos offline', MonitoredHost::where('status', 'offline')->count())
                ->description('No responden')
                ->color('danger')
                ->icon('heroicon-o-x-circle'),

            Stat::make('Servicios online', MonitoredService::where('status', 'online')->count())
                ->description('Puertos activos')
                ->color('success')
                ->icon('heroicon-o-signal'),

            Stat::make('Servicios offline', MonitoredService::where('status', 'offline')->count())
                ->description('Puertos caídos')
                ->color('danger')
                ->icon('heroicon-o-exclamation-triangle'),

            Stat::make('Alertas abiertas', Alert::where('status', 'open')->count())
                ->description('Requieren atención')
                ->color('warning')
                ->icon('heroicon-o-bell-alert'),
        ];
    }
}
