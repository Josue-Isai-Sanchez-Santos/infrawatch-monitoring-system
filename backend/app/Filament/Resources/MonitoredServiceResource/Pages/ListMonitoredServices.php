<?php

namespace App\Filament\Resources\MonitoredServiceResource\Pages;

use App\Filament\Resources\MonitoredServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMonitoredServices extends ListRecords
{
    protected static string $resource = MonitoredServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
