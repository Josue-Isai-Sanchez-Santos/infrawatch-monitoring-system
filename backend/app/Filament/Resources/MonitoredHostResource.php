<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MonitoredHostResource\Pages;
use App\Models\MonitoredHost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

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
                            ->rule('ip')
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
                            ->label('Último estado del agente')
                            ->options([
                                'unknown' => 'Desconocido',
                                'online' => 'Online',
                                'offline' => 'Offline',
                                'warning' => 'Warning',
                                'critical' => 'Critical',
                            ])
                            ->default('unknown')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText(
                                'Estado de solo lectura. Lo actualiza la API del agente.'
                            ),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Agente')
                    ->schema([
                        Forms\Components\TextInput::make('agent_token')
                            ->label('Token del agente')
                            ->password()
                            ->revealable()
                            ->default(
                                fn (string $operation): ?string => $operation === 'create'
                                        ? Str::random(64)
                                        : null
                            )
                            ->formatStateUsing(
                                fn ($state, string $operation): ?string => $operation === 'edit' ? null : $state
                            )
                            ->required(
                                fn (string $operation): bool => $operation === 'create'
                            )
                            ->minLength(64)
                            ->maxLength(64)
                            ->regex('/^[A-Za-z0-9]{64}$/')
                            ->unique(ignoreRecord: true)
                            ->dehydrated(
                                fn (?string $state): bool => filled($state)
                            )
                            ->helperText(
                                'Al crear el equipo, guarda el token generado. '
                                .'Al editar, deja el campo vacío para conservarlo. '
                                .'Introduce un nuevo token de 64 caracteres '
                                .'únicamente si deseas reemplazar el anterior.'
                            ),

                        Forms\Components\DateTimePicker::make('last_seen_at')
                            ->label('Último reporte del agente')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query) => $query->with('services')
            )
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
                    ->label('Último estado agente')
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
                Tables\Columns\TextColumn::make('services_status')
                    ->label('Salud servicios TCP')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'online' => 'success',
                        'critical' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'online' => 'Online',
                        'critical' => 'Crítico',
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
                    ->label('Estado del agente')
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
