<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->isAdmin();
    }

    public function delete(User $actor, User $target): bool
    {
        if (! $actor->isAdmin() || $actor->is($target)) {
            return false;
        }

        // Primera barrera para ocultar la acción en Filament.
        // La operación real debe volver a comprobarlo bajo un bloqueo.
        return $target->role !== 'admin'
            || User::query()->where('role', 'admin')->count() > 1;
    }

    public function deleteAny(User $actor): bool
    {
        // No se permite la eliminación masiva, incluso a administradores.
        return false;
    }

    public function forceDelete(User $actor, User $target): bool
    {
        return false;
    }

    public function forceDeleteAny(User $actor): bool
    {
        return false;
    }
}
