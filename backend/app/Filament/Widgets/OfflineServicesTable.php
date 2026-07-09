<?php

namespace App\Filament\Widgets;

use App\Models\MonitoredService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class OfflineServicesTable extends BaseWidget
{
    protected static ?string $heading = 'Servicios caídos';

    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MonitoredService::query()
                    ->with('host')
                    ->whereIn('status', ['offline', 'critical'])
                    ->latest('last_checked_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('host.name')
                    ->label('Equipo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('host.ip_address')
                    ->label('IP')
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Servicio')
                    ->searchable(),

                Tables\Columns\TextColumn::make('port')
                    ->label('Puerto')
                    ->sortable(),

                Tables\Columns\TextColumn::make('protocol')
                    ->label('Protocolo')
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'offline' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('last_checked_at')
                    ->label('Última revisión')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->emptyStateHeading('No hay servicios caídos')
            ->emptyStateDescription('Todos los servicios registrados están respondiendo correctamente.');
    }
}
