<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HostMetricResource\Pages;
use App\Models\HostMetric;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HostMetricResource extends Resource
{
    protected static ?string $model = HostMetric::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('monitored_host_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('cpu_usage')
                    ->numeric(),
                Forms\Components\TextInput::make('ram_usage')
                    ->numeric(),
                Forms\Components\TextInput::make('disk_usage')
                    ->numeric(),
                Forms\Components\TextInput::make('uptime_seconds')
                    ->numeric(),
                Forms\Components\DateTimePicker::make('recorded_at')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('recorded_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('recorded_at', 'desc')
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
            'index' => Pages\ListHostMetrics::route('/'),
            'create' => Pages\CreateHostMetric::route('/create'),
            'edit' => Pages\EditHostMetric::route('/{record}/edit'),
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
