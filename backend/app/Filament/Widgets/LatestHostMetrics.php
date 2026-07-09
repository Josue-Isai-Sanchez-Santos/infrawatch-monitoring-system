<?php

namespace App\Filament\Widgets;

use App\Models\HostMetric;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestHostMetrics extends BaseWidget
{
    protected static ?string $heading = 'Últimas métricas recibidas';

    protected static ?int $sort = 12;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                HostMetric::query()
                    ->with('host')
                    ->latest('recorded_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('host.name')
                    ->label('Equipo')
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('cpu_usage')
                    ->label('CPU')
                    ->suffix('%')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        (float) $state >= 90 => 'danger',
                        (float) $state >= 75 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('ram_usage')
                    ->label('RAM')
                    ->suffix('%')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        (float) $state >= 90 => 'danger',
                        (float) $state >= 75 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('disk_usage')
                    ->label('Disco')
                    ->suffix('%')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        (float) $state >= 90 => 'danger',
                        (float) $state >= 80 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('uptime_seconds')
                    ->label('Uptime')
                    ->formatStateUsing(function ($state): string {
                        if (! $state) {
                            return 'N/A';
                        }

                        $days = floor($state / 86400);
                        $hours = floor(($state % 86400) / 3600);
                        $minutes = floor(($state % 3600) / 60);

                        return "{$days}d {$hours}h {$minutes}m";
                    }),

                Tables\Columns\TextColumn::make('recorded_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->emptyStateHeading('Sin métricas registradas')
            ->emptyStateDescription('Ejecuta el agente para enviar métricas al backend.');
    }
}
