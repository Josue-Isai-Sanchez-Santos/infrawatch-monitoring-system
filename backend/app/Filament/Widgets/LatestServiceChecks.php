<?php

namespace App\Filament\Widgets;

use App\Models\ServiceCheck;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestServiceChecks extends BaseWidget
{
    protected static ?string $heading = 'Últimos 10 chequeos TCP';

    protected static ?int $sort = 11;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ServiceCheck::query()
                    ->with(['service.host'])
                    ->latest('checked_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('service.host.name')
                    ->label('Equipo')
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Servicio')
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('service.port')
                    ->label('Puerto'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'online' => 'success',
                        'offline' => 'danger',
                        'warning' => 'warning',
                        'critical' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('response_time_ms')
                    ->label('Respuesta')
                    ->suffix(' ms')
                    ->sortable(),

                Tables\Columns\TextColumn::make('checked_at')
                    ->label('Revisado')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->emptyStateHeading('Sin chequeos registrados')
            ->emptyStateDescription('Ejecuta el monitoreo TCP para generar registros.');
    }
}
