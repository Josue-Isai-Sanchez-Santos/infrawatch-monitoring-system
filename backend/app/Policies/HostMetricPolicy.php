<?php

namespace App\Policies;

use App\Models\HostMetric;
use App\Models\User;

class HostMetricPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'technician', 'observer'], true);
    }

    public function view(User $user, HostMetric $hostMetric): bool
    {
        return in_array($user->role, ['admin', 'technician', 'observer'], true);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, HostMetric $hostMetric): bool
    {
        return false;
    }

    public function delete(User $user, HostMetric $hostMetric): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, HostMetric $hostMetric): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, HostMetric $hostMetric): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, HostMetric $hostMetric): bool
    {
        return false;
    }
}
