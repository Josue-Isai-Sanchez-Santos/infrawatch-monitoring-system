<?php

namespace App\Filament\Resources\ServiceCheckResource\Pages;

use App\Filament\Resources\ServiceCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceChecks extends ListRecords
{
    protected static string $resource = ServiceCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
