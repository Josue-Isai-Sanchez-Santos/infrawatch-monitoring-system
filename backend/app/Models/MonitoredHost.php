<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoredHost extends Model
{
    protected $fillable = [
        'name',
        'hostname',
        'ip_address',
        'operating_system',
        'host_type',
        'location',
        'status',
        'agent_token',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(MonitoredService::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(HostMetric::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function getServicesStatusAttribute(): string
    {
        // Solo se consideran protocolos admitidos
        // por el monitor de InfraWatch V2.0.
        $tcpServices = $this->services->filter(
            fn (MonitoredService $service): bool =>
                $service->protocol === 'tcp'
        );

        if ($tcpServices->isEmpty()) {
            return 'unknown';
        }

        // Un solo servicio caído hace que la salud
        // agregada sea crítica.
        $hasOfflineService = $tcpServices->contains(
            fn (MonitoredService $service): bool =>
                $service->last_checked_at !== null
                && in_array(
                    $service->status,
                    ['offline', 'critical'],
                    true
                )
        );

        if ($hasOfflineService) {
            return 'critical';
        }

        // Todos deben haber sido comprobados
        // y encontrarse online.
        $allOnline = $tcpServices->every(
            fn (MonitoredService $service): bool =>
                $service->last_checked_at !== null
                && $service->status === 'online'
        );

        return $allOnline ? 'online' : 'unknown';
    }
}
