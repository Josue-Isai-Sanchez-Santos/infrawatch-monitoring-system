<?php

namespace App\Policies;

use App\Models\MonitoredService;
use App\Models\User;

class MonitoredServicePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'technician', 'observer'], true);
    }

    public function view(User $user, MonitoredService $monitoredService): bool
    {
        return in_array($user->role, ['admin', 'technician', 'observer'], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'technician'], true);
    }

    public function update(User $user, MonitoredService $monitoredService): bool
    {
        return in_array($user->role, ['admin', 'technician'], true);
    }

    public function delete(User $user, MonitoredService $monitoredService): bool
    {
        return $user->role === 'admin';
    }

    public function deleteAny(User $user): bool
    {
        return $user->role === 'admin';
    }
}
