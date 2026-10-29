<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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

    protected $hidden = [
        'agent_token',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $host): void {
            // Generar un token únicamente si el nuevo
            // host no recibió uno explícitamente.
            if (blank($host->agent_token)) {
                $host->agent_token = Str::random(64);
            }
        });
    }

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
        $tcpServices = $this->services->filter(
            fn (MonitoredService $service): bool => $service->protocol === 'tcp'
        );

        if ($tcpServices->isEmpty()) {
            return 'unknown';
        }

        $hasOfflineService = $tcpServices->contains(
            fn (MonitoredService $service): bool => $service->last_checked_at !== null
                && in_array(
                    $service->status,
                    ['offline', 'critical'],
                    true
                )
        );

        if ($hasOfflineService) {
            return 'critical';
        }

        $allOnline = $tcpServices->every(
            fn (MonitoredService $service): bool => $service->last_checked_at !== null
                && $service->status === 'online'
        );

        return $allOnline ? 'online' : 'unknown';
    }
}
