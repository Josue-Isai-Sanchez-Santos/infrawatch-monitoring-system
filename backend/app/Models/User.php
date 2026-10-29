<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Casts de los atributos del usuario.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Determina si el usuario puede acceder
     * a un panel de Filament.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // InfraWatch solo autoriza explícitamente
        // el panel administrativo existente.
        if ($panel->getId() !== 'admin') {
            return false;
        }

        // Solo los roles reconocidos pueden entrar.
        return in_array(
            $this->role,
            ['admin', 'technician', 'observer'],
            true
        );
    }

    /**
     * Comprueba si el usuario es administrador.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Comprueba si el usuario es técnico.
     */
    public function isTechnician(): bool
    {
        return $this->role === 'technician';
    }

    /**
     * Comprueba si el usuario es observador.
     */
    public function isObserver(): bool
    {
        return $this->role === 'observer';
    }

    /**
     * Administradores y técnicos pueden
     * gestionar infraestructura.
     */
    public function canManageInfrastructure(): bool
    {
        return in_array(
            $this->role,
            ['admin', 'technician'],
            true
        );
    }

    /**
     * Identifica al usuario de solo lectura.
     */
    public function canOnlyView(): bool
    {
        return $this->role === 'observer';
    }
}
