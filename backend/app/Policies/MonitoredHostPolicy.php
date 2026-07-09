<?php

namespace App\Policies;

use App\Models\MonitoredHost;
use App\Models\User;

class MonitoredHostPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'technician', 'observer'], true);
    }

    public function view(User $user, MonitoredHost $monitoredHost): bool
    {
        return in_array($user->role, ['admin', 'technician', 'observer'], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'technician'], true);
    }

    public function update(User $user, MonitoredHost $monitoredHost): bool
    {
        return in_array($user->role, ['admin', 'technician'], true);
    }

    public function delete(User $user, MonitoredHost $monitoredHost): bool
    {
        return $user->role === 'admin';
    }

    public function deleteAny(User $user): bool
    {
        return $user->role === 'admin';
    }
}
