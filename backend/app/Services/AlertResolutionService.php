<?php

namespace App\Services;

use App\Events\DashboardUpdated;
use App\Models\Alert;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AlertResolutionService
{
    public function resolve(User $actor, Alert $alert): bool
    {
        $resolved = DB::transaction(function () use ($actor, $alert): bool {
            $current = Alert::query()->lockForUpdate()->findOrFail($alert->getKey());

            Gate::forUser($actor)->authorize('update', $current);

            if ($current->status !== 'open') {
                return false;
            }

            $current->update([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);

            return true;
        });

        if ($resolved) {
            DashboardUpdated::dispatch(
                type: 'alert_resolved',
                message: 'Una alerta fue resuelta manualmente.'
            );
        }

        return $resolved;
    }
}
