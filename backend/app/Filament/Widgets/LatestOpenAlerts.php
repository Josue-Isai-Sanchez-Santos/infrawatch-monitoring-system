<?php

namespace App\Filament\Widgets;

use App\Models\Alert;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestOpenAlerts extends BaseWidget
{
    protected static ?string $heading = 'Últimas 10 alertas abiertas';

    protected static ?int $sort = 10;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Alert::query()
                    ->with(['host', 'service'])
                    ->where('status', 'open')
                    ->latest('triggered_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('severity')
                    ->label('Severidad')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'info' => 'info',
                        'warning' => 'warning',
                        'critical' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('host.name')
                    ->label('Equipo')
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Servicio')
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('triggered_at')
                    ->label('Activada')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->emptyStateHeading('No hay alertas abiertas')
            ->emptyStateDescription('No existen alertas pendientes por revisar.');
    }
}
