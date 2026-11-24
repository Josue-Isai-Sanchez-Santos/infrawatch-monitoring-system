<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Única vía de modificación / eliminación de cuentas desde Filament.
 * La transacción y el bloqueo de administradores protegen frente a
 * cambios concurrentes en PostgreSQL (no bastaría comprobar un COUNT).
 */
class UserAdministrationService
{
    public function update(User $actor, User $target, array $attributes): User
    {
        return DB::transaction(function () use ($actor, $target, $attributes): User {
            $adminCount = $this->lockAndCountAdministrators();
            $actor = User::query()->findOrFail($actor->getKey());
            $target = User::query()->lockForUpdate()->findOrFail($target->getKey());

            Gate::forUser($actor)->authorize('update', $target);

            if (array_key_exists('role', $attributes)
                && ! in_array($attributes['role'], ['admin', 'technician', 'observer'], true)) {
                throw ValidationException::withMessages([
                    'data.role' => 'Selecciona un rol válido.',
                ]);
            }

            $newRole = $attributes['role'] ?? $target->role;

            if ($actor->is($target) && $newRole !== $target->role) {
                throw ValidationException::withMessages([
                    'data.role' => 'No puedes modificar tu propio rol.',
                ]);
            }

            if ($target->role === 'admin' && $newRole !== 'admin' && $adminCount <= 1) {
                throw ValidationException::withMessages([
                    'data.role' => 'No puedes degradar al último administrador.',
                ]);
            }

            // Un campo de contraseña vacío significa conservar el hash.
            if (array_key_exists('password', $attributes) && blank($attributes['password'])) {
                unset($attributes['password']);
            }

            // No confiar en atributos extra suministrados por un cliente.
            $target->fill(Arr::only($attributes, [
                'name', 'email', 'password', 'role',
            ]));
            $target->save();

            return $target;
        }, 3);
    }

    public function delete(User $actor, User $target): void
    {
        DB::transaction(function () use ($actor, $target): void {
            $adminCount = $this->lockAndCountAdministrators();
            $actor = User::query()->findOrFail($actor->getKey());
            $target = User::query()->lockForUpdate()->findOrFail($target->getKey());

            if (! $actor->isAdmin()) {
                throw new AuthorizationException('No puedes administrar usuarios.');
            }

            if ($actor->is($target)) {
                throw new AuthorizationException('No puedes eliminar tu propia cuenta.');
            }

            if ($target->role === 'admin' && $adminCount <= 1) {
                throw new AuthorizationException('No puedes eliminar al último administrador.');
            }

            Gate::forUser($actor)->authorize('delete', $target);

            $target->delete();
        }, 3);
    }

    private function lockAndCountAdministrators(): int
    {
        // Orden fijo para reducir la probabilidad de deadlocks.
        // PostgreSQL conserva estos bloqueos hasta finalizar la transacción.
        return User::query()
            ->where('role', 'admin')
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id'])
            ->count();
    }
}
