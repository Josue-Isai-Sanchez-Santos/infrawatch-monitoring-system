<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MonitoredHostResource\Pages;
use App\Filament\Resources\MonitoredHostResource\RelationManagers;
use App\Models\MonitoredHost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MonitoredHostResource extends Resource
{
    protected static ?string $model = MonitoredHost::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Section::make('Información del equipo')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('hostname')
                        ->label('Hostname')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('ip_address')
                        ->label('Dirección IP')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(45),

                    Forms\Components\TextInput::make('operating_system')
                        ->label('Sistema operativo')
                        ->maxLength(255),

                    Forms\Components\Select::make('host_type')
                        ->label('Tipo de equipo')
                        ->options([
                            'server' => 'Servidor',
                            'workstation' => 'Estación de trabajo',
                            'router' => 'Router',
                            'switch' => 'Switch',
                            'printer' => 'Impresora',
                            'other' => 'Otro',
                        ])
                        ->default('server')
                        ->required(),

                    Forms\Components\TextInput::make('location')
                        ->label('Ubicación')
                        ->maxLength(255),

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
                ])
                ->columns(2),

            Forms\Components\Section::make('Agente')
                ->schema([
                    Forms\Components\TextInput::make('agent_token')
                        ->label('Token del agente')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    Forms\Components\DateTimePicker::make('last_seen_at')
                        ->label('Última conexión'),
                ])
                ->columns(2),
        ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Nombre')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('ip_address')
                ->label('IP')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('host_type')
                ->label('Tipo')
                ->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'server' => 'Servidor',
                    'workstation' => 'Estación',
                    'router' => 'Router',
                    'switch' => 'Switch',
                    'printer' => 'Impresora',
                    default => 'Otro',
                }),

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

            Tables\Columns\TextColumn::make('location')
                ->label('Ubicación')
                ->searchable(),

            Tables\Columns\TextColumn::make('last_seen_at')
                ->label('Última conexión')
                ->dateTime('d/m/Y H:i')
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Creado')
                ->dateTime('d/m/Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
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

            Tables\Filters\SelectFilter::make('host_type')
                ->label('Tipo')
                ->options([
                    'server' => 'Servidor',
                    'workstation' => 'Estación de trabajo',
                    'router' => 'Router',
                    'switch' => 'Switch',
                    'printer' => 'Impresora',
                    'other' => 'Otro',
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
            'index' => Pages\ListMonitoredHosts::route('/'),
            'create' => Pages\CreateMonitoredHost::route('/create'),
            'edit' => Pages\EditMonitoredHost::route('/{record}/edit'),
        ];
    }
}
