<?php

namespace App\Filament\Widgets;

use App\Models\HostMetric;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopResourceUsageHosts extends BaseWidget
{
    protected static ?string $heading = 'Hosts con mayor uso de recursos';

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getLatestMetricsQuery())
            ->columns([
                Tables\Columns\TextColumn::make('host.name')
                    ->label('Equipo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('cpu_usage')
                    ->label('CPU')
                    ->suffix('%')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        (float) $state >= 90 => 'danger',
                        (float) $state >= 75 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('ram_usage')
                    ->label('RAM')
                    ->suffix('%')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        (float) $state >= 90 => 'danger',
                        (float) $state >= 75 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('disk_usage')
                    ->label('Disco')
                    ->suffix('%')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        (float) $state >= 90 => 'danger',
                        (float) $state >= 80 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('recorded_at')
                    ->label('Última métrica')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('cpu_usage', 'desc');
    }

    private function getLatestMetricsQuery(): Builder
    {
        $latestMetricIds = HostMetric::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('monitored_host_id');

        return HostMetric::query()
            ->with('host')
            ->whereIn('id', $latestMetricIds)
            ->orderByDesc('cpu_usage');
    }
}
