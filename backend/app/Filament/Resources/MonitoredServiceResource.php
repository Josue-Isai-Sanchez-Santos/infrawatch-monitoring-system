<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MonitoredServiceResource\Pages;
use App\Models\MonitoredService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MonitoredServiceResource extends Resource
{
    protected static ?string $model = MonitoredService::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Servicio monitoreado')
                    ->schema([
                        Forms\Components\Select::make('monitored_host_id')
                            ->label('Equipo')
                            ->relationship('host', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del servicio')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('port')
                            ->label('Puerto')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(65535),

                        Forms\Components\Select::make('protocol')
                            ->label('Protocolo')
                            ->options([
                                'tcp' => 'TCP',
                                'udp' => 'UDP',
                            ])
                            ->default('tcp')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'unknown' => 'Desconocido',
                                'online' => 'Online',
                                'offline' => 'Offline',
                                'warning' => 'Warning',
                                'critical' => 'Critical',
                            ])
                            ->default('unknown')
                            ->required(),

                        Forms\Components\DateTimePicker::make('last_checked_at')
                            ->label('Última revisión'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('host.name')
                    ->label('Equipo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('host.ip_address')
                    ->label('IP')
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Servicio')
                    ->searchable()
                    ->sortable(),

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
                        'online' => 'success',
                        'offline' => 'danger',
                        'warning' => 'warning',
                        'critical' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'online' => 'Online',
                        'offline' => 'Offline',
                        'warning' => 'Warning',
                        'critical' => 'Critical',
                        default => 'Desconocido',
                    }),

                Tables\Columns\TextColumn::make('last_checked_at')
                    ->label('Última revisión')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
            Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'unknown' => 'Desconocido',
                        'online' => 'Online',
                        'offline' => 'Offline',
                        'warning' => 'Warning',
                        'critical' => 'Critical',
                    ]),
        ])
            ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
            ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonitoredServices::route('/'),
            'create' => Pages\CreateMonitoredService::route('/create'),
            'edit' => Pages\EditMonitoredService::route('/{record}/edit'),
        ];
    }
}
