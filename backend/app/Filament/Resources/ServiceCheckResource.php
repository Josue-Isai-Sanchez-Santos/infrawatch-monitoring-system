<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceCheckResource\Pages;
use App\Models\ServiceCheck;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceCheckResource extends Resource
{
    protected static ?string $model = ServiceCheck::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('monitored_service_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('response_time_ms')
                    ->numeric(),
                Forms\Components\Textarea::make('message')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('checked_at')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service.host.name')
                    ->label('Equipo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Servicio')
                    ->searchable(),

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

                Tables\Columns\TextColumn::make('message')
                    ->label('Mensaje')
                    ->limit(50),

                Tables\Columns\TextColumn::make('checked_at')
                    ->label('Revisado')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('checked_at', 'desc')
            ->filters([
            Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'online' => 'Online',
                        'offline' => 'Offline',
                        'warning' => 'Warning',
                        'critical' => 'Critical',
                    ]),
        ])
            ->actions([
            Tables\Actions\ViewAction::make(),
        ])
            ->bulkActions([]);
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
            'index' => Pages\ListServiceChecks::route('/'),
            'create' => Pages\CreateServiceCheck::route('/create'),
            'edit' => Pages\EditServiceCheck::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
