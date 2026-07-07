<?php

namespace App\Filament\Widgets;

use App\Models\HostMetric;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestHostMetrics extends BaseWidget
{
    protected static ?string $heading = 'Últimas métricas recibidas';

    protected static ?int $sort = 7;

    protected int | string | array $columnSpan = 'full';

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
                    ->searchable(),

                Tables\Columns\TextColumn::make('cpu_usage')
                    ->label('CPU')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ram_usage')
                    ->label('RAM')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('disk_usage')
                    ->label('Disco')
                    ->suffix('%')
                    ->sortable(),

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
            ->defaultSort('recorded_at', 'desc');
    }
}
