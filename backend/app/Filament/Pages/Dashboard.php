<?php

namespace App\Filament\Pages;

use App\Models\HostMetric;
use App\Models\MonitoredHost;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            Select::make('host_id')
                ->label('Equipo para gráficas de CPU, RAM y disco')
                ->placeholder('Selecciona un equipo')
                ->options(fn (): array => MonitoredHost::query()
                    ->orderBy('name')
                    ->orderBy('id')
                    ->get(['id', 'name', 'ip_address'])
                    ->mapWithKeys(fn (MonitoredHost $host): array => [
                        $host->id => "{$host->name} ({$host->ip_address})",
                    ])
                    ->all())
                ->default(fn (): ?int => HostMetric::query()
                    ->orderByDesc('recorded_at')
                    ->orderByDesc('id')
                    ->value('monitored_host_id'))
                ->searchable()
                ->live()
                ->helperText('Las tres gráficas muestran únicamente las últimas 20 lecturas de este equipo.'),
        ]);
    }
}
