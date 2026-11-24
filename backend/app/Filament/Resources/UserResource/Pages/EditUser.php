<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Services\UserAdministrationService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UserAdministrationService::class)->update(
            Auth::user(),
            $record,
            $data,
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('delete')
                ->label('Eliminar usuario')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => Gate::allows('delete', $this->record))
                ->action(function (): void {
                    app(UserAdministrationService::class)->delete(Auth::user(), $this->record);
                    $this->redirect(UserResource::getUrl('index'));
                }),
        ];
    }
}
