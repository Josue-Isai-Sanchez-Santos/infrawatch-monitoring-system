<?php

namespace App\Filament\Resources\HostMetricResource\Pages;

use App\Filament\Resources\HostMetricResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHostMetrics extends ListRecords
{
    protected static string $resource = HostMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
