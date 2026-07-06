<?php

namespace App\Filament\Widgets;

use App\Models\Alert;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestOpenAlerts extends BaseWidget
{
    protected static ?string $heading = 'Últimas alertas abiertas';

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
                    ->searchable(),

                Tables\Columns\TextColumn::make('host.name')
                    ->label('Equipo'),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Servicio'),

                Tables\Columns\TextColumn::make('triggered_at')
                    ->label('Activada')
                    ->dateTime('d/m/Y H:i:s'),
            ]);
    }
}
