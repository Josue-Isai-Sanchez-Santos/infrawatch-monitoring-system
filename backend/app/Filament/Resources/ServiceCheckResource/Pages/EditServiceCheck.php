<?php

namespace App\Filament\Resources\ServiceCheckResource\Pages;

use App\Filament\Resources\ServiceCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceCheck extends EditRecord
{
    protected static string $resource = ServiceCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
