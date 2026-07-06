<?php

namespace App\Filament\Resources\MonitoredHostResource\Pages;

use App\Filament\Resources\MonitoredHostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMonitoredHosts extends ListRecords
{
    protected static string $resource = MonitoredHostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
