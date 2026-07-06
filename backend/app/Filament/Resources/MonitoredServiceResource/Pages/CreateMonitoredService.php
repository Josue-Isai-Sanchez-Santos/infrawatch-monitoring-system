<?php

namespace App\Filament\Resources\MonitoredServiceResource\Pages;

use App\Filament\Resources\MonitoredServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMonitoredService extends CreateRecord
{
    protected static string $resource = MonitoredServiceResource::class;
}
