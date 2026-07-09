<?php

namespace App\Filament\Resources;

use App\Events\DashboardUpdated;
use App\Filament\Resources\AlertResource\Pages;
use App\Models\Alert;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AlertResource extends Resource
{
    protected static ?string $model = Alert::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de alerta')
                    ->schema([
                        Forms\Components\Select::make('monitored_host_id')
                            ->label('Equipo')
                            ->relationship('host', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('monitored_service_id')
                            ->label('Servicio')
                            ->relationship('service', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('type')
                            ->label('Tipo')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('severity')
                            ->label('Severidad')
                            ->options([
                                'info' => 'Info',
                                'warning' => 'Warning',
                                'critical' => 'Critical',
                            ])
                            ->default('warning')
                            ->required(),

                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('message')
                            ->label('Mensaje')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'open' => 'Abierta',
                                'resolved' => 'Resuelta',
                                'ignored' => 'Ignorada',
                            ])
                            ->default('open')
                            ->required(),

                        Forms\Components\DateTimePicker::make('triggered_at')
                            ->label('Fecha de activación')
                            ->required(),

                        Forms\Components\DateTimePicker::make('resolved_at')
                            ->label('Fecha de resolución'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('host.name')
                    ->label('Equipo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Servicio')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'danger',
                        'resolved' => 'success',
                        'ignored' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Abierta',
                        'resolved' => 'Resuelta',
                        'ignored' => 'Ignorada',
                        default => 'Desconocido',
                    }),

                Tables\Columns\TextColumn::make('triggered_at')
                    ->label('Activada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Resuelta')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
            Tables\Filters\SelectFilter::make('severity')
                    ->label('Severidad')
                    ->options([
                        'info' => 'Info',
                        'warning' => 'Warning',
                        'critical' => 'Critical',
                    ]),

            Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'open' => 'Abierta',
                        'resolved' => 'Resuelta',
                        'ignored' => 'Ignorada',
                    ]),
        ])
            ->actions([
            Tables\Actions\Action::make('resolve')
                    ->label('Resolver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->status === 'open')
                    ->action(function ($record): void {
                        $record->update([
                            'status' => 'resolved',
                            'resolved_at' => now(),
                        ]);
                        DashboardUpdated::dispatch(
                            type: 'alert_resolved',
                            message: 'Una alerta fue resuelta manualmente.'
                        );

                    }),
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
            'index' => Pages\ListAlerts::route('/'),
            'create' => Pages\CreateAlert::route('/create'),
            'edit' => Pages\EditAlert::route('/{record}/edit'),
        ];
    }
}
