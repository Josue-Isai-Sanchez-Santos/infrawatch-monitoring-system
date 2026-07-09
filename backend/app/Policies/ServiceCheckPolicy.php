<?php

namespace App\Policies;

use App\Models\ServiceCheck;
use App\Models\User;

class ServiceCheckPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'technician', 'observer'], true);
    }

    public function view(User $user, ServiceCheck $serviceCheck): bool
    {
        return in_array($user->role, ['admin', 'technician', 'observer'], true);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ServiceCheck $serviceCheck): bool
    {
        return false;
    }

    public function delete(User $user, ServiceCheck $serviceCheck): bool
    {
        return $user->role === 'admin';
    }

    public function deleteAny(User $user): bool
    {
        return $user->role === 'admin';
    }
}
